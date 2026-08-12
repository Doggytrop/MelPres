<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSensitiveRoutesAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_activity_logs_from_its_company_only(): void
    {
        [$company, $admin] = $this->tenant('admin');
        [$otherCompany, $otherAdmin] = $this->tenant('admin');

        $this->activityLog($company, $admin, 'Registro visible de la empresa');
        $this->activityLog($otherCompany, $otherAdmin, 'Registro privado de otra empresa');

        $this->actingAs($admin)
            ->get(route('activity-logs.index'))
            ->assertOk()
            ->assertSee('Registro visible de la empresa')
            ->assertDontSee('Registro privado de otra empresa');
    }

    public function test_superadmin_with_an_active_company_can_access_activity_logs(): void
    {
        [, $superadmin] = $this->tenant('superadmin');

        $this->actingAs($superadmin)
            ->get(route('activity-logs.index'))
            ->assertOk();
    }

    public function test_advisor_cannot_access_activity_logs(): void
    {
        [, $advisor] = $this->tenant('advisor');

        $this->actingAs($advisor)
            ->get(route('activity-logs.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_activity_logs(): void
    {
        $this->get(route('activity-logs.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_reset_a_customer_password_and_receives_credentials(): void
    {
        [$company, $admin] = $this->tenant('admin');
        [$customer, $customerUser] = $this->customerWithUser($company);
        $oldPassword = $customerUser->password;

        $this->actingAs($admin)
            ->post(route('customers.reset-password', $customer))
            ->assertRedirect()
            ->assertSessionHas('credentials');

        $credentials = session('credentials');

        $this->assertNotSame($oldPassword, $customerUser->fresh()->password);
        $this->assertTrue(Hash::check($credentials['password'], $customerUser->fresh()->password));
    }

    public function test_superadmin_with_an_active_company_can_reset_a_customer_password(): void
    {
        [$company, $superadmin] = $this->tenant('superadmin');
        [$customer] = $this->customerWithUser($company);

        $this->actingAs($superadmin)
            ->post(route('customers.reset-password', $customer))
            ->assertRedirect()
            ->assertSessionHas('credentials');
    }

    public function test_advisor_cannot_reset_a_customer_password_or_see_the_button(): void
    {
        [$company, $advisor] = $this->tenant('advisor');
        [$customer] = $this->customerWithUser($company);

        $this->actingAs($advisor)
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertDontSee(route('customers.reset-password', $customer));

        $this->actingAs($advisor)
            ->post(route('customers.reset-password', $customer))
            ->assertForbidden();
    }

    public function test_admin_receives_404_when_resetting_a_customer_from_another_company(): void
    {
        [, $admin] = $this->tenant('admin');
        [$otherCompany] = $this->tenant('admin');
        [$otherCustomer] = $this->customerWithUser($otherCompany);

        $this->actingAs($admin)
            ->post(route('customers.reset-password', $otherCustomer))
            ->assertNotFound();
    }

    public function test_guest_is_redirected_from_customer_password_reset(): void
    {
        [$company] = $this->tenant('admin');
        [$customer] = $this->customerWithUser($company);

        $this->post(route('customers.reset-password', $customer))
            ->assertRedirect(route('login'));
    }

    private function tenant(string $role): array
    {
        $company = Company::factory()->create();
        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'started_at' => now(),
            'current_period_start' => now(),
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => $role,
        ]);

        return [$company, $user];
    }

    private function customerWithUser(Company $company): array
    {
        $customer = Customer::factory()->for($company)->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'role' => 'customer',
        ]);

        return [$customer, $user];
    }

    private function activityLog(Company $company, User $user, string $description): ActivityLog
    {
        return ActivityLog::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'action' => 'create',
            'module' => 'customers',
            'description' => $description,
            'ip_address' => '127.0.0.1',
        ]);
    }
}
