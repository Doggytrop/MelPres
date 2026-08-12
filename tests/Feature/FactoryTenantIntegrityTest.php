<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use Database\Factories\ActivityLogFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoryTenantIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_factory_creates_a_valid_active_company(): void
    {
        $company = Company::factory()->create();

        $this->assertNotEmpty($company->name);
        $this->assertNotEmpty($company->slug);
        $this->assertSame('active', $company->status);
        $this->assertSame('MXN', $company->currency_code);
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'slug' => $company->slug,
        ]);
    }

    public function test_user_factory_creates_unique_users_in_coherent_companies(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->assertNotNull($first->company);
        $this->assertNotNull($second->company);
        $this->assertSame($first->company_id, $first->company->id);
        $this->assertSame($second->company_id, $second->company->id);
        $this->assertNotSame($first->email, $second->email);
        $this->assertNotSame($first->phone, $second->phone);
    }

    public function test_customer_factory_reuses_an_explicit_company(): void
    {
        $company = Company::factory()->create();
        $companyCount = Company::query()->count();

        $customer = Customer::factory()->for($company)->create();

        $this->assertSame($company->id, $customer->company_id);
        $this->assertSame($companyCount, Company::query()->count());
    }

    public function test_loan_factory_derives_company_from_explicit_customer(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->for($company)->create();
        $companyCount = Company::query()->count();

        $loan = Loan::factory()->for($customer)->create();

        $this->assertSame($customer->id, $loan->customer_id);
        $this->assertSame($customer->company_id, $loan->company_id);
        $this->assertSame($companyCount, Company::query()->count());
        $this->assertGreaterThan(0, (float) $loan->original_amount);
        $this->assertLessThanOrEqual(
            (float) $loan->original_amount,
            (float) $loan->remaining_balance
        );
    }

    public function test_payment_factory_keeps_loan_customer_and_actor_in_one_tenant(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->for($company)->create();
        $loan = Loan::factory()->for($customer)->create();
        $actor = User::factory()->for($company)->create(['role' => 'collector']);
        $companyCount = Company::query()->count();

        $payment = Payment::factory()
            ->for($loan)
            ->for($actor, 'recordedBy')
            ->create();

        $this->assertSame($company->id, $payment->company_id);
        $this->assertSame($company->id, $payment->loan->company_id);
        $this->assertSame($company->id, $payment->loan->customer->company_id);
        $this->assertSame($company->id, $payment->recordedBy->company_id);
        $this->assertSame($companyCount, Company::query()->count());
    }

    public function test_payment_factory_builds_a_coherent_default_tenant_graph(): void
    {
        $payment = Payment::factory()->create();

        $this->assertSame($payment->company_id, $payment->loan->company_id);
        $this->assertSame(
            $payment->company_id,
            $payment->loan->customer->company_id
        );
        $this->assertSame(
            $payment->company_id,
            $payment->recordedBy->company_id
        );
    }

    public function test_activity_log_factory_satisfies_tenant_guard_triggers(): void
    {
        $log = ActivityLogFactory::new()->create();
        $actor = $log->user;
        $systemLog = ActivityLogFactory::new()->withoutUser()->create();

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertSame($actor->company_id, $log->company_id);
        $this->assertSame($actor->id, $log->user_id);
        $this->assertDatabaseHas('activity_logs', [
            'id' => $log->id,
            'company_id' => $actor->company_id,
            'user_id' => $actor->id,
        ]);
        $this->assertNotNull($systemLog->company_id);
        $this->assertNull($systemLog->user_id);
    }

    public function test_factories_do_not_depend_on_company_id_one(): void
    {
        Company::factory()->create();
        $company = Company::factory()->create();
        $customer = Customer::factory()->for($company)->create();
        $loan = Loan::factory()->for($customer)->create();

        $this->assertNotSame(1, $company->id);
        $this->assertSame($company->id, $customer->company_id);
        $this->assertSame($company->id, $loan->company_id);
    }
}
