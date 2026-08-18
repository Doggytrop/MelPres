<?php

namespace Tests\Unit;

use App\Models\Loan;
use App\Services\LoanPaymentStateService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoanPaymentStateServiceTest extends TestCase
{
    private LoanPaymentStateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LoanPaymentStateService();
    }

    public function test_partial_payment_keeps_oldest_obligation_open(): void
    {
        $loan = $this->loan('2026-08-15');
        $allocation = $this->service->simulate($loan, 60, '2026-08-15');

        $this->assertSame(0, $allocation->periodsCovered);
        $this->assertSame(40.0, $allocation->currentPeriodBalance);
        $this->assertSame('2026-08-15', $allocation->nextPaymentDate);
        $this->assertSame(0.0, $allocation->creditGenerated);
    }

    public function test_partial_balance_and_new_period_are_both_due(): void
    {
        $loan = $this->loan('2026-08-15', currentBalance: 40);
        $state = $this->service->state($loan, '2026-08-16');

        $this->assertSame(2, $state->duePeriods);
        $this->assertSame(140.0, $state->dueAmount);
    }

    public function test_three_due_and_150_covers_one_and_half_without_credit(): void
    {
        $loan = $this->loan('2026-08-13');
        $allocation = $this->service->simulate($loan, 150, '2026-08-15');

        $this->assertSame(1, $allocation->periodsCovered);
        $this->assertSame(50.0, $allocation->currentPeriodBalance);
        $this->assertSame('2026-08-14', $allocation->nextPaymentDate);
        $this->assertSame(0.0, $allocation->creditGenerated);
    }

    public function test_three_due_and_300_covers_exactly_three(): void
    {
        $allocation = $this->service->simulate($this->loan('2026-08-13'), 300, '2026-08-15');

        $this->assertSame(3, $allocation->periodsCovered);
        $this->assertSame('2026-08-16', $allocation->nextPaymentDate);
        $this->assertSame(0.0, $allocation->creditGenerated);
    }

    public function test_three_due_and_350_generates_credit_only_after_all_are_covered(): void
    {
        $allocation = $this->service->simulate($this->loan('2026-08-13'), 350, '2026-08-15');

        $this->assertSame(3, $allocation->periodsCovered);
        $this->assertSame(50.0, $allocation->creditGenerated);
        $this->assertSame('2026-08-16', $allocation->nextPaymentDate);
    }

    public function test_only_exigible_obligation_is_covered_and_future_dates_do_not_advance(): void
    {
        $allocation = $this->service->simulate($this->loan('2026-08-15'), 300, '2026-08-15');

        $this->assertSame(1, $allocation->periodsCovered);
        $this->assertSame('2026-08-16', $allocation->nextPaymentDate);
        $this->assertSame(200.0, $allocation->creditGenerated);
        $this->assertSame(200.0, $allocation->paymentCredit);
    }

    public function test_credit_consumption_does_not_count_as_new_capital_reduction(): void
    {
        $loan = $this->loan('2026-08-15', credit: 50);
        $allocation = $this->service->simulate($loan, 50, '2026-08-15');

        $this->assertSame(50.0, $allocation->capitalPayment);
        $this->assertSame(50.0, $allocation->creditConsumed);
        $this->assertSame(100.0, $allocation->periodicAmountApplied);
        $this->assertSame(0.0, $allocation->paymentCredit);
        $this->assertSame(1, $allocation->periodsCovered);
    }

    public function test_reading_state_never_consumes_credit_or_moves_date(): void
    {
        $loan = $this->loan('2026-08-16', credit: 100);
        $state = $this->service->state($loan, '2026-08-15');

        $this->assertSame(100.0, $state->paymentCredit);
        $this->assertSame('2026-08-16', $loan->next_payment_date->toDateString());
        $this->assertSame('100.00', $loan->payment_credit);
    }

    public function test_full_credit_is_consumed_only_by_explicit_zero_cash_operation(): void
    {
        $loan = $this->loan('2026-08-15', credit: 100);
        $allocation = $this->service->simulate($loan, 0, '2026-08-15');

        $this->assertSame(100.0, $allocation->creditConsumed);
        $this->assertSame(0.0, $allocation->capitalPayment);
        $this->assertSame(1, $allocation->periodsCovered);
        $this->assertSame('2026-08-16', $allocation->nextPaymentDate);
    }

    public function test_penalty_is_removed_before_periodic_allocation(): void
    {
        $loan = $this->loan('2026-08-15');
        $loan->accumulated_penalty = 30;
        $allocation = $this->service->simulate($loan, 60, '2026-08-15');

        $this->assertSame(30.0, $allocation->penaltyPayment);
        $this->assertSame(30.0, $allocation->periodicAmountApplied);
        $this->assertSame(70.0, $allocation->currentPeriodBalance);
        $this->assertSame(0.0, $allocation->creditGenerated);
    }

    public function test_interest_is_removed_before_periodic_allocation(): void
    {
        $loan = $this->loan('2026-08-15');
        $loan->pending_interest = 30;
        $allocation = $this->service->simulate($loan, 150, '2026-08-15');

        $this->assertSame(30.0, $allocation->interestPayment);
        $this->assertSame(100.0, $allocation->periodicAmountApplied);
        $this->assertSame(20.0, $allocation->creditGenerated);
    }

    public function test_multiple_partials_complete_exactly_one_obligation(): void
    {
        $loan = $this->loan('2026-08-15');

        foreach ([20, 30, 50] as $amount) {
            $allocation = $this->service->simulate($loan, $amount, '2026-08-15');
            $loan->current_period_balance = $allocation->currentPeriodBalance;
            $loan->next_payment_date = $allocation->nextPaymentDate;
        }

        $this->assertSame(1, $allocation->periodsCovered);
        $this->assertSame('2026-08-16', $allocation->nextPaymentDate);
    }

    public function test_grace_distinguishes_due_from_overdue(): void
    {
        $loan = $this->loan('2026-08-14');
        $loan->grace_days = 2;

        $inGrace = $this->service->state($loan, '2026-08-15');
        $overdue = $this->service->state($loan, '2026-08-17');

        $this->assertSame(2, $inGrace->duePeriods);
        $this->assertSame(0, $inGrace->overduePeriods);
        $this->assertSame(2, $inGrace->gracePeriods);
        $this->assertGreaterThan(0, $overdue->overduePeriods);
    }

    #[DataProvider('frequencyProvider')]
    public function test_frequency_advancement(string $frequency, string $start, string $expected): void
    {
        $this->assertSame($expected, $this->service->addPeriods($start, $frequency)->toDateString());
    }

    public static function frequencyProvider(): array
    {
        return [
            ['daily', '2026-08-15', '2026-08-16'],
            ['weekly', '2026-08-15', '2026-08-22'],
            ['biweekly', '2026-08-15', '2026-08-30'],
            ['monthly', '2026-01-28', '2026-02-28'],
            ['monthly', '2026-01-30', '2026-03-02'],
            ['monthly', '2026-01-31', '2026-03-03'],
            ['monthly', '2024-01-31', '2024-03-02'],
            ['monthly', '2026-12-31', '2027-01-31'],
        ];
    }

    public function test_due_date_limits_generated_obligations(): void
    {
        $loan = $this->loan('2026-08-13');
        $loan->due_date = '2026-08-14';
        $state = $this->service->state($loan, '2026-08-20');

        $this->assertSame(2, $state->duePeriods);
        $allocation = $this->service->simulate($loan, 500, '2026-08-20');
        $this->assertSame(2, $allocation->periodsCovered);
        $this->assertNull($allocation->nextPaymentDate);
        $this->assertSame(300.0, $allocation->creditGenerated);
    }

    public function test_interest_loan_uses_explicit_legacy_branch(): void
    {
        $loan = $this->loan('2026-08-15');
        $loan->type = 'interest';
        $loan->daily_payment = null;
        $loan->original_amount = 1000;
        $loan->interest_rate = 10;

        $state = $this->service->state($loan, '2026-08-15');

        $this->assertFalse($state->installmentSchedule);
        $this->assertSame(1, $state->duePeriods);
        $this->assertSame(100.0, $state->dueAmount);
    }

    private function loan(string $nextDate, ?float $currentBalance = null, float $credit = 0): Loan
    {
        return new Loan([
            'type' => 'daily',
            'payment_frequency' => 'daily',
            'number_of_periods' => 30,
            'original_amount' => 3000,
            'remaining_balance' => 3000,
            'interest_rate' => 0,
            'accrued_interest' => 0,
            'pending_interest' => 0,
            'daily_payment' => 100,
            'current_period_balance' => $currentBalance,
            'payment_credit' => $credit,
            'accumulated_penalty' => 0,
            'grace_days' => 0,
            'start_date' => Carbon::parse($nextDate)->subDay(),
            'due_date' => Carbon::parse($nextDate)->addDays(29),
            'next_payment_date' => $nextDate,
            'status' => 'active',
        ]);
    }
}
