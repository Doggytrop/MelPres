<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantCompanyNameSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_updates_the_official_company_name_and_legacy_setting_atomically(): void
    {
        [$companyA, $admin] = $this->tenant('Empresa Anterior');
        [$companyB] = $this->tenant('Empresa Sin Cambios');
        $originalSlug = $companyA->slug;
        $originalSubscription = $companyA->subscription->only([
            'status',
            'started_at',
            'current_period_start',
            'current_period_end',
            'grace_until',
        ]);

        Setting::create([
            'company_id' => $companyA->id,
            'key' => 'company_name',
            'value' => $companyA->name,
            'type' => 'string',
            'group' => 'company',
        ]);

        $this->actingAs($admin)
            ->post(route('settings.update'), ['company_name' => 'Empresa Renombrada'])
            ->assertRedirect(route('settings.index'));

        $companyA->refresh();
        $this->assertSame('Empresa Renombrada', $companyA->name);
        $this->assertSame($originalSlug, $companyA->slug);
        $this->assertSame('Empresa Sin Cambios', $companyB->fresh()->name);
        $this->assertEquals(
            $originalSubscription,
            $companyA->subscription->fresh()->only(array_keys($originalSubscription))
        );
        $this->assertDatabaseHas('settings', [
            'company_id' => $companyA->id,
            'key' => 'company_name',
            'value' => 'Empresa Renombrada',
        ]);

        $log = ActivityLog::where('company_id', $companyA->id)
            ->where('module', 'settings')
            ->latest('id')
            ->firstOrFail();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame(['name' => 'Empresa Anterior'], $log->old_values);
        $this->assertSame(['name' => 'Empresa Renombrada'], $log->new_values);

        $this->get(route('dashboard'))->assertOk()->assertSee('Empresa Renombrada');
        $this->get(route('settings.index'))
            ->assertOk()
            ->assertSee('value="Empresa Renombrada"', false);

        $superadmin = User::factory()->create([
            'company_id' => null,
            'role' => 'superadmin',
        ]);
        $this->actingAs($superadmin)
            ->get(route('superadmin.companies.index'))
            ->assertOk()
            ->assertSee('Empresa Renombrada');
        $this->get(route('superadmin.companies.show', $companyA))
            ->assertOk()
            ->assertSee('Empresa Renombrada');
    }

    public function test_non_admin_tenant_roles_cannot_change_the_official_name(): void
    {
        [$company] = $this->tenant('Empresa Protegida');

        foreach (['advisor', 'collector', 'customer'] as $role) {
            $user = User::factory()->for($company)->create(['role' => $role]);

            $this->actingAs($user)
                ->post(route('settings.update'), ['company_name' => 'Cambio No Autorizado']);

            $this->assertSame('Empresa Protegida', $company->fresh()->name);
        }
    }

    private function tenant(string $name): array
    {
        $company = Company::factory()->create(['name' => $name]);
        $subscription = CompanySubscription::create([
            'company_id' => $company->id,
            'status' => SubscriptionStatus::ACTIVE,
            'started_at' => now(),
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
        $admin = User::factory()->for($company)->create(['role' => 'admin']);
        $company->setRelation('subscription', $subscription);

        return [$company, $admin];
    }
}
