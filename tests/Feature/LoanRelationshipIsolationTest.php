<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanRelationshipIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_only_loads_customers_from_the_active_company(): void
    {
        [$company, $admin] = $this->tenant('Empresa Uno', 'empresa-uno');
        [$otherCompany] = $this->tenant('Empresa Dos', 'empresa-dos');
        $customer = $this->customer($company, 'Cliente Propio');
        $otherCustomer = $this->customer($otherCompany, 'Cliente Ajeno');

        $this->loan($customer);
        $this->loan($otherCustomer);

        $this->actingAs($admin)
            ->get(route('loans.index'))
            ->assertOk()
            ->assertSee('Cliente Propio')
            ->assertDontSee('Cliente Ajeno');
    }

    public function test_customer_search_only_returns_active_loans_from_the_active_company(): void
    {
        [$company, $admin] = $this->tenant('Empresa Uno', 'empresa-uno');
        [$otherCompany] = $this->tenant('Empresa Dos', 'empresa-dos');
        $customer = $this->customer($company, 'Cliente Busqueda');
        $ownLoan = $this->loan($customer);
        $inactiveLoan = $this->loan($customer);
        $inactiveLoan->update(['status' => 'paid']);
        $otherCustomer = $this->customer($otherCompany, 'Cliente Busqueda');
        $otherLoan = $this->loan($otherCustomer);

        $response = $this->actingAs($admin)
            ->getJson(route('loans.search-customer', ['q' => 'Cliente Busqueda']))
            ->assertOk()
            ->assertJsonPath('0.id', $customer->id);

        $this->assertCount(1, $response->json());
        $this->assertSame(
            [$ownLoan->id],
            array_column($response->json('0.loans'), 'id')
        );
        $this->assertNotContains(
            $inactiveLoan->id,
            array_column($response->json('0.loans'), 'id')
        );
        $this->assertNotContains(
            $otherLoan->id,
            array_column($response->json('0.loans'), 'id')
        );
    }

    public function test_edit_works_when_loan_and_customer_belong_to_the_active_company(): void
    {
        [$company, $admin] = $this->tenant('Empresa Uno', 'empresa-uno');
        $customer = $this->customer($company, 'Cliente Editable');
        $loan = $this->loan($customer);

        $this->actingAs($admin)
            ->get(route('loans.edit', $loan))
            ->assertOk()
            ->assertSee('Cliente Editable');
    }

    public function test_edit_view_does_not_lazy_load_the_customer_relation(): void
    {
        [$company, $admin] = $this->tenant('Empresa Uno', 'empresa-uno');
        $customer = $this->customer($company, 'Cliente Precargado');
        $loan = $this->loan($customer);

        Model::preventLazyLoading();

        try {
            $this->actingAs($admin)
                ->get(route('loans.edit', $loan))
                ->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_user_from_another_company_cannot_access_the_loan(): void
    {
        [$company] = $this->tenant('Empresa Uno', 'empresa-uno');
        [, $otherAdmin] = $this->tenant('Empresa Dos', 'empresa-dos');
        $customer = $this->customer($company, 'Cliente Protegido');
        $loan = $this->loan($customer);

        $this->actingAs($otherAdmin)
            ->get(route('loans.edit', $loan))
            ->assertNotFound();
    }

    private function tenant(string $name, string $slug): array
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

    private function customer(Company $company, string $firstName): Customer
    {
        return Customer::create([
            'company_id' => $company->id,
            'first_name' => $firstName,
            'last_name' => 'Prueba',
            'phone' => fake()->unique()->numerify('##########'),
            'status' => 'active',
        ]);
    }

    private function loan(Customer $customer): Loan
    {
        return Loan::create([
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
            'type' => 'interest',
            'payment_frequency' => 'daily',
            'number_of_periods' => 10,
            'original_amount' => 100,
            'remaining_balance' => 100,
            'interest_rate' => 10,
            'start_date' => now()->subDay()->toDateString(),
            'next_payment_date' => now()->toDateString(),
            'status' => 'active',
        ]);
    }
}
