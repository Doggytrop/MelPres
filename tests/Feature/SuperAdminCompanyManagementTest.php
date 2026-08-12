<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminCompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_list_and_view_companies(): void
    {
        [$company] = $this->tenant();

        $this->actingAs($this->superadmin())
            ->get(route('superadmin.companies.index'))
            ->assertOk()
            ->assertSee($company->name)
            ->assertSee('Alta:')
            ->assertSee('Estado operativo')
            ->assertSee('Suscripción')
            ->assertSee('sa-table-scroll--companies', false)
            ->assertSee('class="sa-button sa-button--primary"', false)
            ->assertSee('class="sa-button sa-button--secondary table-link"', false)
            ->assertDontSee($company->slug);

        $detail = $this->get(route('superadmin.companies.show', $company))
            ->assertOk()
            ->assertSee($company->name)
            ->assertDontSee($company->slug)
            ->assertSee('Renovar suscripción')
            ->assertSee('Administrador principal')
            ->assertSee('Fecha de alta')
            ->assertSee('Suscripción: Activa')
            ->assertSee('Operación: Activa')
            ->assertSee('Sin gracia')
            ->assertDontSee('Periodo histórico')
            ->assertDontSee('No configurado');

        $this->assertSame(1, substr_count($detail->getContent(), 'Sin vencimiento'));
    }

    public function test_tenant_roles_cannot_access_company_management(): void
    {
        foreach (['admin', 'advisor', 'collector', 'customer'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('superadmin.companies.index'))
                ->assertForbidden();
        }
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('superadmin.companies.index'))
            ->assertRedirect(route('login'));
    }

    public function test_tenant_user_cannot_access_a_company_by_direct_url(): void
    {
        [$company, $admin] = $this->tenant();

        $this->actingAs($admin)
            ->get(route('superadmin.companies.show', $company))
            ->assertForbidden();
    }

    public function test_index_shows_counts_and_the_oldest_admin_without_lazy_loading(): void
    {
        [$company, $oldestAdmin] = $this->tenant();
        User::factory()->for($company)->create(['role' => 'admin']);
        $customer = Customer::factory()->for($company)->create();
        Loan::factory()->for($customer)->create();

        Model::preventLazyLoading();

        try {
            $this->actingAs($this->superadmin())
                ->get(route('superadmin.companies.index'))
                ->assertOk()
                ->assertSee($oldestAdmin->name)
                ->assertSee('2')
                ->assertSee('1');
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_index_translates_operational_and_subscription_statuses(): void
    {
        $states = [
            [SubscriptionStatus::ACTIVE, 'Activa'],
            [SubscriptionStatus::PAST_DUE, 'Pago pendiente'],
            [SubscriptionStatus::SUSPENDED, 'Suspendida'],
            [SubscriptionStatus::CANCELLED, 'Cancelada'],
        ];

        foreach ($states as [$status, $label]) {
            $company = Company::factory()->create();
            CompanySubscription::create([
                'company_id' => $company->id,
                'status' => $status,
                'started_at' => now(),
                'current_period_start' => now(),
            ]);
        }
        Company::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($this->superadmin())
            ->get(route('superadmin.companies.index'))
            ->assertOk()
            ->assertSee('Inactiva');

        foreach ($states as [, $label]) {
            $response->assertSee($label);
        }
    }

    public function test_expired_active_subscription_is_listed_and_filtered_as_past_due(): void
    {
        $expiredCompany = Company::factory()->create(['name' => 'Empresa Vencida']);
        CompanySubscription::create([
            'company_id' => $expiredCompany->id,
            'status' => SubscriptionStatus::ACTIVE,
            'started_at' => now()->subYear(),
            'current_period_start' => now()->subYear(),
            'current_period_end' => now()->subDay(),
        ]);
        [$activeCompany] = $this->tenant();

        $this->actingAs($this->superadmin())
            ->get(route('superadmin.companies.index', [
                'search' => 'Vencida',
                'subscription_status' => 'past_due',
            ]))
            ->assertOk()
            ->assertSee($expiredCompany->name)
            ->assertSee('Pago pendiente')
            ->assertDontSee($activeCompany->name);
    }

    public function test_general_company_editing_is_not_exposed_to_superadmin(): void
    {
        [$company] = $this->tenant();

        $this->actingAs($this->superadmin())
            ->get('/superadmin/companies/'.$company->id.'/edit')
            ->assertNotFound();
        $this->put('/superadmin/companies/'.$company->id, [
            'name' => 'Cambio no permitido',
        ])->assertMethodNotAllowed();

        $this->assertNotSame('Cambio no permitido', $company->fresh()->name);
    }

    public function test_suspend_blocks_tenant_access_and_reactivate_restores_it(): void
    {
        [$company, $admin] = $this->tenant();
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->post(route('superadmin.companies.suspend', $company))
            ->assertRedirect(route('superadmin.companies.show', $company));

        $this->assertSame(SubscriptionStatus::SUSPENDED, $company->subscription->fresh()->status);
        $this->assertSame('active', $company->fresh()->status);
        $this->actingAs($admin)->get(route('dashboard'))->assertForbidden();

        $this->actingAs($superadmin)
            ->post(route('superadmin.companies.reactivate', $company));

        $this->assertSame(SubscriptionStatus::ACTIVE, $company->subscription->fresh()->status);
        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_past_due_with_future_grace_allows_access_and_expired_grace_blocks_it(): void
    {
        [$company, $admin] = $this->tenant();
        $superadmin = $this->superadmin();
        $company->subscription->update([
            'status' => SubscriptionStatus::PAST_DUE,
            'current_period_end' => now()->subDays(2),
        ]);

        $this->actingAs($superadmin)
            ->post(route('superadmin.companies.grace.update', $company), [
                'grace_until' => now()->addDay()->toDateTimeString(),
            ]);
        $this->actingAs($admin)->get(route('dashboard'))->assertOk();

        $this->actingAs($superadmin)
            ->post(route('superadmin.companies.grace.update', $company), [
                'grace_until' => now()->subDay()->toDateTimeString(),
            ]);
        $this->actingAs($admin)->get(route('dashboard'))->assertForbidden();
    }

    public function test_cancel_blocks_access_without_modifying_tenant_data(): void
    {
        [$company, $admin] = $this->tenant();
        $customer = Customer::factory()->for($company)->create();
        $loan = Loan::factory()->for($customer)->create();
        $payment = Payment::factory()->for($loan)->create([
            'company_id' => $company->id,
            'recorded_by' => $admin->id,
        ]);

        $this->actingAs($this->superadmin())
            ->post(route('superadmin.companies.cancel', $company));

        $this->assertSame(SubscriptionStatus::CANCELLED, $company->subscription->fresh()->status);
        $this->actingAs($admin)->get(route('dashboard'))->assertForbidden();
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
        $this->assertDatabaseHas('loans', ['id' => $loan->id]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id]);
    }

    public function test_company_cannot_be_physically_deleted_from_superadmin_routes(): void
    {
        [$company] = $this->tenant();

        $this->actingAs($this->superadmin())
            ->delete('/superadmin/companies/'.$company->id)
            ->assertMethodNotAllowed();

        $this->assertDatabaseHas('companies', ['id' => $company->id]);
    }

    private function tenant(): array
    {
        $company = Company::factory()->create();
        $subscription = CompanySubscription::create([
            'company_id' => $company->id,
            'status' => SubscriptionStatus::ACTIVE,
            'started_at' => now(),
            'current_period_start' => now(),
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
}
