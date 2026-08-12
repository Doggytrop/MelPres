<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SaasActivityLog;
use App\Models\User;
use App\Services\SaasAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class SaasActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_creation_records_global_audit_with_actor_and_http_metadata(): void
    {
        $superadmin = $this->superadmin();

        $this->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_USER_AGENT' => 'MelPresAuditTest/1.0',
        ])->actingAs($superadmin)
            ->post(route('superadmin.companies.store'), $this->companyData())
            ->assertRedirect();

        $company = Company::where('slug', 'empresa-auditada')->firstOrFail();
        $log = SaasActivityLog::where('action', 'company_created')->firstOrFail();

        $this->assertSame($superadmin->id, $log->actor_user_id);
        $this->assertSame($superadmin->name, $log->actor_name);
        $this->assertSame(Company::class, $log->subject_type);
        $this->assertSame($company->id, $log->subject_id);
        $this->assertSame('203.0.113.10', $log->ip_address);
        $this->assertSame('MelPresAuditTest/1.0', $log->user_agent);
        $this->assertSame('active', $log->new_values['subscription']['status']);
        $this->assertSame(1, $log->new_values['subscription']['contracted_years']);
        $this->assertNotNull($log->new_values['subscription']['started_at']);
        $this->assertNotNull($log->new_values['subscription']['current_period_end']);

        $serialized = json_encode($log->new_values);
        $this->assertStringNotContainsString('password', strtolower($serialized));
        $this->assertStringNotContainsString('password123', $serialized);

        $this->actingAs($superadmin)
            ->get(route('superadmin.activity-logs.index'))
            ->assertOk()
            ->assertDontSee('Origen')
            ->assertDontSee('203.0.113.10')
            ->assertDontSee('Agente registrado');
    }

    public function test_activity_view_translates_actions_and_subject_without_exposing_namespaces(): void
    {
        [$company] = $this->tenant();
        $this->actingAs($this->superadmin());
        app(SaasAuditService::class)->record('company_updated', $company);

        $this->get(route('superadmin.activity-logs.index'))
            ->assertOk()
            ->assertSee('class="sa-button sa-button--primary"', false)
            ->assertSee('class="sa-button sa-button--secondary"', false)
            ->assertSee('class="sa-button-group"', false)
            ->assertSee('Empresa actualizada')
            ->assertSee('Empresa')
            ->assertSee($company->name)
            ->assertDontSee(Company::class)
            ->assertDontSee('name="subject_type"', false);
    }

    public function test_all_subscription_transitions_are_audited(): void
    {
        [$company] = $this->tenant();
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)->post(route('superadmin.companies.suspend', $company));
        $this->actingAs($superadmin)->post(route('superadmin.companies.reactivate', $company));
        $this->actingAs($superadmin)->post(route('superadmin.companies.cancel', $company));
        $this->actingAs($superadmin)->post(route('superadmin.companies.reactivate', $company));
        $this->actingAs($superadmin)->post(route('superadmin.companies.renew', $company), [
            'subscription_years' => 1,
        ]);
        $renewal = $company->subscription()->firstOrFail()->current_period_end;
        $this->actingAs($superadmin)->post(route('superadmin.companies.grace.update', $company), [
            'grace_until' => $renewal->copy()->addDays(3)->toDateTimeString(),
        ]);
        $this->actingAs($superadmin)->delete(route('superadmin.companies.grace.remove', $company));

        foreach ([
            'company_suspended' => 'suspended',
            'company_reactivated' => 'active',
            'company_cancelled' => 'cancelled',
            'company_renewed' => 'active',
            'company_grace_updated' => 'active',
            'company_grace_removed' => 'active',
        ] as $action => $status) {
            $log = SaasActivityLog::where('action', $action)->firstOrFail();
            $this->assertSame($superadmin->id, $log->actor_user_id);
            $this->assertSame($company->id, $log->subject_id);
            $this->assertSame($status, $log->new_values['subscription']['status']);
        }
    }

    public function test_actor_reference_becomes_null_but_snapshot_is_preserved_after_physical_delete(): void
    {
        [$company] = $this->tenant();
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)->post(route('superadmin.companies.suspend', $company));
        DB::table('users')->where('id', $superadmin->id)->delete();

        $log = SaasActivityLog::where('action', 'company_suspended')->firstOrFail();
        $this->assertNull($log->actor_user_id);
        $this->assertSame($superadmin->name, $log->actor_name);
        $this->assertNull($log->actor);
    }

    public function test_audit_service_removes_sensitive_keys_recursively(): void
    {
        [$company] = $this->tenant();
        $this->actingAs($this->superadmin());

        $log = app(SaasAuditService::class)->record(
            'company_updated',
            $company,
            null,
            [
                'name' => 'Visible',
                'password' => 'hidden',
                'nested' => [
                    'api_token' => 'hidden-token',
                    'value' => 'Visible nested value',
                ],
            ]
        );

        $this->assertSame('Visible', $log->new_values['name']);
        $this->assertArrayNotHasKey('password', $log->new_values);
        $this->assertArrayNotHasKey('api_token', $log->new_values['nested']);
        $this->assertSame('Visible nested value', $log->new_values['nested']['value']);
    }

    public function test_saas_logs_are_immutable_through_eloquent(): void
    {
        [$company] = $this->tenant();
        $this->actingAs($this->superadmin());
        $log = app(SaasAuditService::class)->record('company_updated', $company);

        try {
            $log->update(['description' => 'Alterado']);
            $this->fail('Updating a SaaS audit log should fail.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('inmutables', $exception->getMessage());
        }

        try {
            $log->delete();
            $this->fail('Deleting a SaaS audit log should fail.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('no pueden eliminarse', $exception->getMessage());
        }

        $this->assertDatabaseHas('saas_activity_logs', ['id' => $log->id]);
    }

    public function test_activity_list_is_available_only_to_superadmin(): void
    {
        $this->actingAs($this->superadmin())
            ->get(route('superadmin.activity-logs.index'))
            ->assertOk();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('superadmin.activity-logs.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_saas_activity_list(): void
    {
        $this->get(route('superadmin.activity-logs.index'))
            ->assertRedirect(route('login'));
    }

    public function test_provisioning_rolls_back_when_critical_audit_fails(): void
    {
        $data = $this->companyData();
        $superadmin = $this->superadmin();

        $this->mock(SaasAuditService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('record')
                ->once()
                ->andThrow(new RuntimeException('Forced audit failure'));
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($superadmin)
                ->post(route('superadmin.companies.store'), $data);
            $this->fail('Provisioning should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced audit failure', $exception->getMessage());
        }

        $this->assertDatabaseMissing('companies', ['slug' => $data['slug']]);
        $this->assertDatabaseMissing('users', ['email' => $data['admin_email']]);
    }

    public function test_subscription_transition_rolls_back_when_critical_audit_fails(): void
    {
        [$company] = $this->tenant();
        $superadmin = $this->superadmin();

        $this->mock(SaasAuditService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('record')
                ->once()
                ->andThrow(new RuntimeException('Forced audit failure'));
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($superadmin)
                ->post(route('superadmin.companies.suspend', $company));
            $this->fail('Suspension should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced audit failure', $exception->getMessage());
        }

        $this->assertSame(SubscriptionStatus::ACTIVE, $company->subscription->fresh()->status);
        $this->assertDatabaseMissing('saas_activity_logs', ['action' => 'company_suspended']);
    }

    private function tenant(): array
    {
        $company = Company::factory()->create();
        $subscription = CompanySubscription::create([
            'company_id' => $company->id,
            'status' => SubscriptionStatus::ACTIVE,
            'started_at' => '2026-07-01 00:00:00',
            'current_period_start' => '2026-07-01 00:00:00',
        ]);
        $admin = User::factory()->for($company)->create(['role' => 'admin']);
        $company->setRelation('subscription', $subscription);

        return [$company, $admin];
    }

    private function superadmin(): User
    {
        return User::factory()->create([
            'company_id' => null,
            'role' => 'superadmin',
        ]);
    }

    private function companyData(): array
    {
        return [
            'name' => 'Empresa Auditada',
            'slug' => 'empresa-auditada',
            'email' => 'contacto@auditada.test',
            'phone' => '6623000000',
            'address' => 'Hermosillo, Sonora',
            'timezone' => 'America/Hermosillo',
            'currency_code' => 'MXN',
            'currency_symbol' => '$',
            'admin_name' => 'Admin Auditado',
            'admin_email' => 'admin@auditada.test',
            'admin_phone' => '6623000001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'started_at' => '2026-07-31 09:00:00',
            'current_period_start' => '2026-07-31 09:00:00',
            'subscription_years' => 1,
            'grace_until' => '2026-09-05 09:00:00',
        ];
    }

}
