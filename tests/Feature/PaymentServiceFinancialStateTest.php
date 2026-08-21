<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use App\Services\CompanyContext;
use App\Services\PaymentService;
use App\Services\PenaltyService;
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

    public function test_financed_daily_payments_persist_exact_contractual_interest_and_capital_totals(): void
    {
        [$loan] = $this->contextualFinancedLoan();
        $service = app(PaymentService::class);

        for ($period = 0; $period < 30; $period++) {
            $service->applyPayment($loan->fresh(), [
                'amount_paid' => 100,
                'payment_date' => Carbon::parse('2026-08-15')->addDays($period)->toDateString(),
            ]);
        }

        $payments = Payment::where('loan_id', $loan->id)->get();

        $this->assertSame('2500.00', number_format((float) $payments->sum('capital_payment'), 2, '.', ''));
        $this->assertSame('500.00', number_format((float) $payments->sum('interest_payment'), 2, '.', ''));
        $this->assertSame('3000.00', number_format((float) $payments->sum('amount_paid'), 2, '.', ''));
        $this->assertSame('paid', $loan->fresh()->status);
        $this->assertSame('0.00', $loan->fresh()->remaining_balance);
    }

    public function test_admin_selected_range_covers_two_future_periods_without_generating_credit(): void
    {
        [$loan] = $this->contextualFortyEightFinancedLoan();

        $payment = app(PaymentService::class)->applyPayment($loan, [
            'amount_paid' => 96,
            'payment_date' => '2026-08-15',
            'selected_through_date' => '2026-08-16',
        ]);
        $after = $loan->fresh();

        $this->assertSame('80.00', $payment->capital_payment);
        $this->assertSame('16.00', $payment->interest_payment);
        $this->assertSame('96.00', $payment->periodic_amount_applied);
        $this->assertSame(2, $payment->periods_covered);
        $this->assertSame('0.00', $payment->credit_generated);
        $this->assertSame('0.00', $payment->carry_over);
        $this->assertSame('0.00', $after->payment_credit);
        $this->assertSame('48.00', $after->current_period_balance);
        $this->assertSame('2026-08-17', $after->next_payment_date->toDateString());
    }

    public function test_admin_selected_range_uses_the_partial_current_period_before_the_next_one(): void
    {
        [$loan] = $this->contextualFortyEightFinancedLoan();
        $loan->update(['current_period_balance' => 20]);

        $payment = app(PaymentService::class)->applyPayment($loan->fresh(), [
            'amount_paid' => 68,
            'payment_date' => '2026-08-15',
            'selected_through_date' => '2026-08-16',
        ]);
        $after = $loan->fresh();

        $this->assertSame('68.00', $payment->periodic_amount_applied);
        $this->assertSame(2, $payment->periods_covered);
        $this->assertSame('0.00', $payment->credit_generated);
        $this->assertSame('2026-08-17', $after->next_payment_date->toDateString());
    }

    public function test_admin_selected_range_with_insufficient_cash_only_covers_completed_periods(): void
    {
        [$loan] = $this->contextualFortyEightFinancedLoan();

        $payment = app(PaymentService::class)->applyPayment($loan, [
            'amount_paid' => 50,
            'payment_date' => '2026-08-15',
            'selected_through_date' => '2026-08-16',
        ]);
        $after = $loan->fresh();

        $this->assertSame('50.00', $payment->periodic_amount_applied);
        $this->assertSame(1, $payment->periods_covered);
        $this->assertSame('0.00', $payment->credit_generated);
        $this->assertSame('46.00', $after->current_period_balance);
        $this->assertSame('2026-08-16', $after->next_payment_date->toDateString());
    }

    public function test_admin_selected_range_generates_credit_only_for_a_real_excess(): void
    {
        [$loan] = $this->contextualFortyEightFinancedLoan();

        $payment = app(PaymentService::class)->applyPayment($loan, [
            'amount_paid' => 120,
            'payment_date' => '2026-08-15',
            'selected_through_date' => '2026-08-16',
        ]);
        $after = $loan->fresh();

        $this->assertSame('96.00', $payment->periodic_amount_applied);
        $this->assertSame(2, $payment->periods_covered);
        $this->assertSame('24.00', $payment->credit_generated);
        $this->assertSame('24.00', $after->payment_credit);
    }

    public function test_admin_selected_range_rejects_dates_before_the_next_obligation_or_outside_the_schedule(): void
    {
        [$loan] = $this->contextualFortyEightFinancedLoan();
        $service = app(PaymentService::class);

        foreach (['2026-08-14', '2026-09-14'] as $selectedThroughDate) {
            try {
                $service->applyPayment($loan->fresh(), [
                    'amount_paid' => 96,
                    'payment_date' => '2026-08-15',
                    'selected_through_date' => $selectedThroughDate,
                ]);
                $this->fail('La fecha manipulada debía rechazarse.');
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $this->assertArrayHasKey('selected_through_date', $exception->errors());
            }
        }
    }

    public function test_unselected_payment_keeps_collector_style_credit_behavior(): void
    {
        [$loan] = $this->contextualFortyEightFinancedLoan();

        $payment = app(PaymentService::class)->applyPayment($loan, [
            'amount_paid' => 96,
            'payment_date' => '2026-08-15',
        ]);

        $this->assertSame(1, $payment->periods_covered);
        $this->assertSame('48.00', $payment->credit_generated);
        $this->assertSame('48.00', $loan->fresh()->payment_credit);
    }

    public function test_payment_keeps_the_contract_active_when_overdue_installments_remain_before_due_date(): void
    {
        [$loan] = $this->contextualLoan();
        Carbon::setTestNow('2026-08-20');
        $loan->update([
            'original_amount' => 12000,
            'remaining_balance' => 12000,
            'daily_payment' => 400,
            'number_of_periods' => 30,
            'start_date' => '2026-08-15',
            'next_payment_date' => '2026-08-16',
            'due_date' => '2026-09-14',
        ]);

        $payment = app(PaymentService::class)->applyPayment($loan->fresh(), [
            'amount_paid' => 800,
            'payment_date' => '2026-08-20',
            'authorize_future_periods' => true,
        ]);
        $after = $loan->fresh();

        $this->assertSame(2, $payment->periods_covered);
        $this->assertSame('active', $after->status);
        $this->assertSame('2026-08-18', $after->next_payment_date->toDateString());
    }

    public function test_payment_marks_a_pending_contract_overdue_only_after_its_due_date(): void
    {
        [$loan] = $this->contextualLoan();
        Carbon::setTestNow('2026-09-15');
        $loan->update([
            'due_date' => '2026-09-14',
            'next_payment_date' => '2026-09-15',
        ]);

        app(PaymentService::class)->applyPayment($loan->fresh(), [
            'amount_paid' => 100,
            'payment_date' => '2026-09-15',
        ]);

        $this->assertSame('overdue', $loan->fresh()->status);
    }

    public function test_penalty_does_not_mark_a_pending_contract_overdue_before_its_due_date(): void
    {
        [$loan] = $this->contextualLoan();
        Carbon::setTestNow('2026-08-20');
        $loan->update([
            'penalty_type' => 'fixed',
            'penalty_value' => 25,
            'grace_days' => 0,
            'next_payment_date' => '2026-08-16',
            'due_date' => '2026-09-14',
        ]);

        app(PenaltyService::class)->processLoan($loan->fresh());
        $after = $loan->fresh();

        $this->assertSame('25.00', $after->accumulated_penalty);
        $this->assertSame('active', $after->status);
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

    private function contextualFinancedLoan(): array
    {
        [$loan, $company, $user] = $this->contextualLoan();
        $loan->update([
            'original_amount' => 2500,
            'remaining_balance' => 3000,
            'interest_rate' => 20,
            'accrued_interest' => 500,
            'daily_payment' => 100,
        ]);

        return [$loan->fresh(), $company, $user];
    }

    private function contextualFortyEightFinancedLoan(): array
    {
        [$loan, $company, $user] = $this->contextualLoan();
        $loan->update([
            'original_amount' => 1200,
            'remaining_balance' => 1440,
            'interest_rate' => 20,
            'accrued_interest' => 240,
            'daily_payment' => 48,
        ]);

        return [$loan->fresh(), $company, $user];
    }
}
