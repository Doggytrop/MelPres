<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_customer_user_and_activity_log_with_company_and_credentials(): void
    {
        [$company, $admin] = $this->createCompanyAndAdmin('Empresa Uno', 'empresa-uno');

        $response = $this->actingAs($admin)->post(route('customers.store'), $this->customerData());

        $customer = Customer::where('phone', '6515130051')->firstOrFail();
        $user = User::where('customer_id', $customer->id)->firstOrFail();

        $response
            ->assertRedirect(route('customers.show', $customer))
            ->assertSessionHas('credentials', function (array $credentials) use ($user) {
                return $credentials['email'] === '6515130051@melpres.app'
                    && $credentials['phone'] === '6515130051'
                    && Hash::check($credentials['password'], $user->password);
            });

        $this->assertSame($company->id, $customer->company_id);
        $this->assertSame($company->id, $user->company_id);
        $this->assertSame('customer', $user->role);
        $this->assertSame($customer->id, $user->customer_id);

        $this->assertDatabaseHas('activity_logs', [
            'company_id' => $company->id,
            'action' => 'create',
            'module' => 'customers',
            'model_type' => Customer::class,
            'model_id' => $customer->id,
        ]);
    }

    public function test_it_rolls_back_when_user_phone_already_exists(): void
    {
        [, $admin] = $this->createCompanyAndAdmin('Empresa Uno', 'empresa-uno');

        User::factory()->create([
            'phone' => '6515130051',
            'email' => 'existing@example.com',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('customers.create'))
            ->post(route('customers.store'), $this->customerData());

        $response
            ->assertRedirect(route('customers.create'))
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseMissing('customers', ['phone' => '6515130051']);
        $this->assertDatabaseMissing('activity_logs', ['module' => 'customers']);
    }

    public function test_it_rolls_back_when_generated_user_email_already_exists(): void
    {
        [, $admin] = $this->createCompanyAndAdmin('Empresa Uno', 'empresa-uno');

        User::factory()->create([
            'phone' => '6515130099',
            'email' => '6515130051@melpres.app',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('customers.create'))
            ->post(route('customers.store'), $this->customerData());

        $response
            ->assertRedirect(route('customers.create'))
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseMissing('customers', ['phone' => '6515130051']);
        $this->assertDatabaseMissing('activity_logs', ['module' => 'customers']);
    }

    public function test_it_never_leaves_an_orphan_when_user_creation_is_cancelled(): void
    {
        [, $admin] = $this->createCompanyAndAdmin('Empresa Uno', 'empresa-uno');

        Event::listen('eloquent.creating: '.User::class, function (User $user) {
            return $user->role === 'customer' ? false : null;
        });

        $response = $this->actingAs($admin)
            ->from(route('customers.create'))
            ->post(route('customers.store'), $this->customerData());

        $response
            ->assertRedirect(route('customers.create'))
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseMissing('customers', ['phone' => '6515130051']);
        $this->assertDatabaseMissing('users', ['phone' => '6515130051']);
        $this->assertDatabaseMissing('activity_logs', ['module' => 'customers']);
    }

    public function test_it_does_not_link_a_user_from_another_company(): void
    {
        [, $admin] = $this->createCompanyAndAdmin('Empresa Uno', 'empresa-uno');
        [$otherCompany] = $this->createCompanyAndAdmin('Empresa Dos', 'empresa-dos');

        $otherUser = User::factory()->create([
            'company_id' => $otherCompany->id,
            'phone' => '6515130051',
            'email' => 'other-company@example.com',
            'customer_id' => null,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('customers.create'))
            ->post(route('customers.store'), $this->customerData());

        $response
            ->assertRedirect(route('customers.create'))
            ->assertSessionHasErrors('phone');

        $this->assertNull($otherUser->fresh()->customer_id);
        $this->assertDatabaseMissing('customers', ['phone' => '6515130051']);
    }

    public function test_phone_is_required_and_no_records_are_created_without_it(): void
    {
        [, $admin] = $this->createCompanyAndAdmin('Empresa Uno', 'empresa-uno');
        $data = $this->customerData();
        $data['phone'] = '';

        $response = $this->actingAs($admin)
            ->from(route('customers.create'))
            ->post(route('customers.store'), $data);

        $response
            ->assertRedirect(route('customers.create'))
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseMissing('customers', [
            'first_name' => 'Carlos',
            'last_name' => 'Arvizu',
        ]);
        $this->assertDatabaseMissing('activity_logs', ['module' => 'customers']);
    }

    private function createCompanyAndAdmin(string $name, string $slug): array
    {
        $company = Company::create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
        ]);

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'started_at' => now(),
            'current_period_start' => now(),
        ]);

        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
        ]);

        return [$company, $admin];
    }

    private function customerData(): array
    {
        return [
            'first_name' => 'Carlos',
            'last_name' => 'Arvizu',
            'phone' => '6515130051',
            'document_type' => 'ine',
            'document_number' => 'CARLOS-TEST-001',
            'status' => 'active',
        ];
    }
}
