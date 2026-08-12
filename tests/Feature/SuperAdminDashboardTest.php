<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use App\Services\SaasAuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_commercial_counts_and_recent_company_activity(): void
    {
        $active = $this->companyWithSubscription('Empresa Activa', SubscriptionStatus::ACTIVE, now()->addDays(20));
        $this->companyWithSubscription('Empresa Vencida', SubscriptionStatus::ACTIVE, now()->subDay());
        $this->companyWithSubscription('Empresa Suspendida', SubscriptionStatus::SUSPENDED, now()->addYear());

        $superadmin = User::create([
            'company_id' => null,
            'name' => 'Superadmin Dashboard',
            'email' => 'superadmin-dashboard@example.test',
            'phone' => null,
            'password' => 'password',
            'role' => 'superadmin',
            'customer_id' => null,
        ]);
        $this->actingAs($superadmin);
        app(SaasAuditService::class)->record('company_renewed', $active);

        Model::preventLazyLoading();

        try {
            $response = $this->get(route('superadmin.dashboard'))
                ->assertOk()
                ->assertSee('Empresas totales')
                ->assertSee('Pago pendiente')
                ->assertSee('Suspendidas')
                ->assertSee('Renovaciones próximas')
                ->assertSee('sa-table-scroll--dashboard', false)
                ->assertSee('class="sa-button sa-button--primary"', false)
                ->assertSee('class="sa-button sa-button--secondary"', false)
                ->assertSee('Suscripción renovada')
                ->assertSee($active->name)
                ->assertDontSee(Company::class);

            $this->assertSame([
                'total' => 4,
                'active' => 2,
                'past_due' => 1,
                'suspended' => 1,
                'renewals_soon' => 1,
            ], $response->viewData('summary'));
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    private function companyWithSubscription(
        string $name,
        SubscriptionStatus $status,
        mixed $periodEnd
    ): Company {
        $company = Company::factory()->create(['name' => $name]);
        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => $status,
            'started_at' => now()->subYear(),
            'current_period_start' => now()->subYear(),
            'current_period_end' => $periodEnd,
            'suspended_at' => $status === SubscriptionStatus::SUSPENDED ? now() : null,
        ]);

        return $company;
    }
}
