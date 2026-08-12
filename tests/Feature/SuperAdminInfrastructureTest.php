<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SuperAdminInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_access_its_dashboard_without_a_company(): void
    {
        $superadmin = $this->createGlobalSuperadmin();

        $response = $this->actingAs($superadmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Resumen general')
            ->assertSee('Panel de control')
            ->assertDontSee('Centro de administración')
            ->assertDontSee('MelPres SaaS')
            ->assertSee('class="sa-button sa-button--secondary sa-logout"', false)
            ->assertSee('aria-controls="superadmin-navigation"', false)
            ->assertSee('aria-expanded="false"', false);

        foreach ([
            route('superadmin.dashboard'),
            route('superadmin.companies.index'),
            route('superadmin.activity-logs.index'),
            route('logout'),
        ] as $url) {
            $response->assertSee($url, false);
        }

        $response
            ->assertDontSee('href="'.route('dashboard').'"', false)
            ->assertDontSee('href="'.route('customers.index').'"', false)
            ->assertDontSee('href="'.route('loans.index').'"', false);
    }

    public function test_admin_cannot_access_superadmin_dashboard(): void
    {
        $this->assertTenantRoleIsForbidden('admin');
    }

    public function test_advisor_cannot_access_superadmin_dashboard(): void
    {
        $this->assertTenantRoleIsForbidden('advisor');
    }

    public function test_collector_cannot_access_superadmin_dashboard(): void
    {
        $this->assertTenantRoleIsForbidden('collector');
    }

    public function test_customer_cannot_access_superadmin_dashboard(): void
    {
        $this->assertTenantRoleIsForbidden('customer');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('superadmin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_superadmin_login_redirects_without_tenant_activity_log(): void
    {
        $superadmin = $this->createGlobalSuperadmin();

        $this->post('/login', [
            'login' => $superadmin->email,
            'password' => 'password',
        ])->assertRedirect(route('superadmin.dashboard'));

        $this->assertAuthenticatedAs($superadmin);
        $this->assertDatabaseMissing('activity_logs', [
            'user_id' => $superadmin->id,
            'action' => 'login',
            'module' => 'auth',
        ]);
    }

    public function test_admin_login_keeps_tenant_dashboard_and_activity_log_flow(): void
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->create(['role' => 'admin']);

        $this->post('/login', [
            'login' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseHas('activity_logs', [
            'company_id' => $company->id,
            'user_id' => $admin->id,
            'action' => 'login',
            'module' => 'auth',
        ]);
    }

    public function test_superadmin_route_does_not_require_tenant_middleware(): void
    {
        $middleware = Route::getRoutes()
            ->getByName('superadmin.dashboard')
            ->gatherMiddleware();

        $this->assertContains('auth', $middleware);
        $this->assertContains('superadmin', $middleware);
        $this->assertNotContains('company.required', $middleware);
        $this->assertNotContains('redirect.customer', $middleware);
    }

    private function assertTenantRoleIsForbidden(string $role): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->create(['role' => $role]);

        $this->actingAs($user)
            ->get(route('superadmin.dashboard'))
            ->assertForbidden();
    }

    private function createGlobalSuperadmin(): User
    {
        return User::factory()->create([
            'company_id' => null,
            'role' => 'superadmin',
        ]);
    }
}
