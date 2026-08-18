<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use App\Services\CompanyContext;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceFinancialStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_partial_payments_on_same_day_complete_one_obligation(): void
    {
        [$loan] = $this->contextualLoan();
        $service = app(PaymentService::class);

        $first = $service->applyPayment($loan, ['amount_paid' => 60, 'payment_date' => '2026-08-15']);
        $afterFirst = $loan->fresh();

        $this->assertSame(0, $first->periods_covered);
        $this->assertSame('40.00', $afterFirst->current_period_balance);
        $this->assertSame('2026-08-15', $afterFirst->next_payment_date->toDateString());

        $second = $service->applyPayment($afterFirst, ['amount_paid' => 40, 'payment_date' => '2026-08-15']);
        $afterSecond = $loan->fresh();

        $this->assertSame(2, Payment::where('loan_id', $loan->id)->count());
        $this->assertSame(1, $second->periods_covered);
        $this->assertSame('2026-08-16', $afterSecond->next_payment_date->toDateString());
    }

    public function test_consuming_credit_does_not_reduce_remaining_balance_twice(): void
    {
        [$loan] = $this->contextualLoan();
        $service = app(PaymentService::class);

        $first = $service->applyPayment($loan, ['amount_paid' => 150, 'payment_date' => '2026-08-15']);
        $afterFirst = $loan->fresh();

        $this->assertSame('50.00', $first->credit_generated);
        $this->assertSame('2850.00', $afterFirst->remaining_balance);

        Carbon::setTestNow('2026-08-16');
        $second = $service->applyPayment($afterFirst, ['amount_paid' => 50, 'payment_date' => '2026-08-16']);
        $afterSecond = $loan->fresh();

        $this->assertSame('50.00', $second->credit_consumed);
        $this->assertSame('100.00', $second->periodic_amount_applied);
        $this->assertSame('2800.00', $afterSecond->remaining_balance);
        $this->assertSame('0.00', $afterSecond->payment_credit);
        Carbon::setTestNow();
    }

    public function test_payment_service_rejects_a_loan_from_another_tenant(): void
    {
        [$loan] = $this->contextualLoan();
        $other = Company::create(['name' => 'Otra', 'slug' => 'otra', 'status' => 'active']);
        app(CompanyContext::class)->setCompany($other);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(PaymentService::class)->applyPayment($loan, ['amount_paid' => 100, 'payment_date' => '2026-08-15']);
    }

    private function contextualLoan(): array
    {
        Carbon::setTestNow('2026-08-15');
        $company = Company::create(['name' => 'Empresa', 'slug' => 'empresa-financial', 'status' => 'active']);
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $customer = Customer::create([
            'company_id' => $company->id,
            'first_name' => 'Cliente',
            'last_name' => 'Prueba',
            'status' => 'active',
        ]);
        $loan = Loan::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'type' => 'daily',
            'payment_frequency' => 'daily',
            'number_of_periods' => 30,
            'original_amount' => 3000,
            'remaining_balance' => 3000,
            'interest_rate' => 0,
            'accrued_interest' => 0,
            'pending_interest' => 0,
            'daily_payment' => 100,
            'accumulated_penalty' => 0,
            'grace_days' => 0,
            'start_date' => '2026-08-14',
            'due_date' => '2026-09-13',
            'next_payment_date' => '2026-08-15',
            'status' => 'active',
        ]);

        $this->actingAs($user);
        app(CompanyContext::class)->setCompany($company);

        return [$loan, $company, $user];
    }
}
