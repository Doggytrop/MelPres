<?php

namespace Tests\Unit;

use App\Models\Loan;
use Carbon\Carbon;
use Tests\TestCase;

class LoanContractualStatusTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_pending_loan_is_active_on_its_contract_due_date(): void
    {
        Carbon::setTestNow('2026-09-14');
        $loan = $this->pendingLoan('2026-09-14');

        $loan->syncContractualStatus();

        $this->assertSame('active', $loan->status);
    }

    public function test_overdue_installments_and_penalty_do_not_expire_a_future_contract(): void
    {
        Carbon::setTestNow('2026-08-20');
        $loan = $this->pendingLoan('2026-09-14');
        $loan->next_payment_date = '2026-08-16';
        $loan->grace_days = 0;
        $loan->accumulated_penalty = 25;

        $loan->syncContractualStatus();

        $this->assertSame('active', $loan->status);
    }

    public function test_pending_loan_is_overdue_only_after_its_contract_due_date(): void
    {
        Carbon::setTestNow('2026-09-15');
        $loan = $this->pendingLoan('2026-09-14');

        $loan->syncContractualStatus();

        $this->assertSame('overdue', $loan->status);
    }

    public function test_financially_settled_loan_is_paid(): void
    {
        Carbon::setTestNow('2026-08-20');
        $loan = $this->pendingLoan('2026-08-19');
        $loan->remaining_balance = 0;
        $loan->pending_interest = 0;

        $loan->syncContractualStatus();

        $this->assertSame('paid', $loan->status);
    }

    public function test_refinanced_status_is_preserved(): void
    {
        Carbon::setTestNow('2026-09-15');
        $loan = $this->pendingLoan('2026-09-14');
        $loan->status = 'refinanced';

        $loan->syncContractualStatus();

        $this->assertSame('refinanced', $loan->status);
    }

    private function pendingLoan(string $dueDate): Loan
    {
        return new Loan([
            'status' => 'active',
            'remaining_balance' => 100,
            'pending_interest' => 0,
            'due_date' => $dueDate,
        ]);
    }
}
