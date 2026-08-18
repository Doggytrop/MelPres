<?php

namespace App\Services;

use App\Models\Loan;
use Carbon\Carbon;

class LoanPaymentStateService
{
    public function state(Loan $loan, Carbon|string|null $asOf = null): LoanPaymentState
    {
        $date = $this->date($asOf);
        $base = $this->baseAmount($loan);
        $scheduled = $this->supportsInstallmentSchedule($loan);
        $oldest = $loan->next_payment_date?->copy()->startOfDay();
        $current = $this->currentBalance($loan, $base);
        $credit = max(0, round((float) ($loan->payment_credit ?? 0), 2));

        if (! $oldest || $loan->status === 'paid' || $base <= 0) {
            return new LoanPaymentState($base, $loan->payment_frequency, null, 0, 0, 0, 0, 0, 0, $credit, 0, 0, $scheduled);
        }

        if (! $scheduled) {
            $due = $oldest->lte($date) ? 1 : 0;
            $overdue = $due && $date->gt($oldest->copy()->addDays((int) ($loan->grace_days ?? 0))) ? 1 : 0;
            $grace = $due && ! $overdue ? 1 : 0;
            $dueAmount = $due ? $current : 0;
            $effective = max(0, round($current - $credit, 2));

            return new LoanPaymentState($base, $loan->payment_frequency, $oldest->toDateString(), $current, $due, $overdue, $grace, $overdue ? $current : 0, $dueAmount, $credit, $effective, max(0, $dueAmount - $credit), false);
        }

        $dates = $this->dueDates($loan, $date);
        $duePeriods = count($dates);
        $overduePeriods = 0;
        $overdueAmount = 0;

        foreach ($dates as $index => $dueDate) {
            if ($date->gt($dueDate->copy()->addDays((int) ($loan->grace_days ?? 0)))) {
                $overduePeriods++;
                $overdueAmount += $index === 0 ? $current : $base;
            }
        }

        $dueAmount = $duePeriods > 0
            ? round($current + max(0, $duePeriods - 1) * $base, 2)
            : 0;
        $dueAmount = min($dueAmount, $this->periodicCapacity($loan));
        $overdueAmount = min(round($overdueAmount, 2), $dueAmount);
        $effective = max(0, round($current - $credit, 2));

        return new LoanPaymentState(
            $base,
            $loan->payment_frequency,
            $oldest->toDateString(),
            $current,
            $duePeriods,
            $overduePeriods,
            $duePeriods - $overduePeriods,
            $overdueAmount,
            $dueAmount,
            $credit,
            $effective,
            max(0, round($dueAmount - $credit, 2)),
            true,
        );
    }

    public function simulate(Loan $loan, float $amountReceived, Carbon|string|null $asOf = null): PaymentAllocation
    {
        $date = $this->date($asOf);
        $state = $this->state($loan, $date);
        $cash = round($amountReceived, 2);
        $penalty = min($cash, max(0, (float) $loan->accumulated_penalty));
        $cash = round($cash - $penalty, 2);
        $interest = min($cash, max(0, (float) $loan->pending_interest));
        $cash = round($cash - $interest, 2);
        $capital = min($cash, max(0, (float) $loan->remaining_balance));

        if (! $state->installmentSchedule) {
            return $this->simulateLegacyInterest($loan, $state, $date, $penalty, $interest, $capital, $cash);
        }

        $creditConsumed = min($state->paymentCredit, $state->dueAmount);
        $periodicCash = min($cash, max(0, $state->dueAmount - $creditConsumed));
        $periodicApplied = round($creditConsumed + $periodicCash, 2);
        $creditGenerated = max(0, round($cash - $periodicCash, 2));
        $newCredit = max(0, round($state->paymentCredit - $creditConsumed + $creditGenerated, 2));

        [$covered, $nextDate, $currentBalance] = $this->advanceOpenObligation($loan, $state, $periodicApplied, $date);

        return new PaymentAllocation(
            $penalty,
            $interest,
            $capital,
            $periodicCash,
            $periodicApplied,
            $creditGenerated,
            $creditConsumed,
            $newCredit,
            $currentBalance,
            $covered,
            $nextDate,
        );
    }

    public function addPeriods(Carbon|string $date, string $frequency, int $periods = 1): Carbon
    {
        $result = Carbon::parse($date)->startOfDay();

        for ($i = 0; $i < $periods; $i++) {
            $result = match ($frequency) {
                'daily' => $result->addDay(),
                'weekly' => $result->addWeek(),
                'biweekly' => $result->addDays(15),
                'monthly' => $result->addMonth(),
                default => $result->addMonth(),
            };
        }

        return $result;
    }

    public function supportsInstallmentSchedule(Loan $loan): bool
    {
        return in_array($loan->type, ['daily', 'term'], true);
    }

    private function simulateLegacyInterest(Loan $loan, LoanPaymentState $state, Carbon $date, float $penalty, float $interest, float $capital, float $cash): PaymentAllocation
    {
        // Los préstamos renovables de interés conservan la semántica histórica:
        // cada operación avanza un período desde la fecha del cobro. No se les
        // aplica acumulación de cuotas ni crédito hasta definir su contrato propio.
        $periodic = min($cash, $state->baseAmount);
        $next = $this->addPeriods($date, $loan->payment_frequency)->toDateString();

        return new PaymentAllocation($penalty, $interest, $capital, $periodic, $periodic, 0, 0, 0, $state->baseAmount, 1, $next);
    }

    private function advanceOpenObligation(Loan $loan, LoanPaymentState $state, float $applied, Carbon $asOf): array
    {
        if ($state->duePeriods === 0 || $applied <= 0) {
            return [0, $state->oldestPendingDate, $state->currentPeriodBalance];
        }

        $remainingApplied = $applied;
        $balance = $state->currentPeriodBalance;
        $date = Carbon::parse($state->oldestPendingDate);
        $covered = 0;

        while ($date->lte($asOf) && $remainingApplied >= $balance && $balance > 0) {
            $remainingApplied = round($remainingApplied - $balance, 2);
            $covered++;
            $date = $this->addPeriods($date, $loan->payment_frequency);

            if (! $this->isWithinContract($loan, $date)) {
                return [$covered, null, 0];
            }

            $balance = min($state->baseAmount, $this->periodicCapacity($loan));
        }

        if ($date->lte($asOf) && $remainingApplied > 0) {
            $balance = max(0, round($balance - $remainingApplied, 2));
        }

        return [$covered, $date->toDateString(), $balance];
    }

    private function dueDates(Loan $loan, Carbon $asOf): array
    {
        $dates = [];
        $date = $loan->next_payment_date?->copy()->startOfDay();
        $guard = max(1, (int) ($loan->number_of_periods ?? 365));

        while ($date && $date->lte($asOf) && $this->isWithinContract($loan, $date) && count($dates) < $guard) {
            $dates[] = $date->copy();
            $date = $this->addPeriods($date, $loan->payment_frequency);
        }

        return $dates;
    }

    private function isWithinContract(Loan $loan, Carbon $date): bool
    {
        return ! $loan->due_date || $date->lte($loan->due_date);
    }

    private function currentBalance(Loan $loan, float $base): float
    {
        $stored = $loan->current_period_balance;
        $balance = $stored === null ? $base : (float) $stored;

        return max(0, round(min($balance, $this->periodicCapacity($loan)), 2));
    }

    private function periodicCapacity(Loan $loan): float
    {
        return max(0, round((float) $loan->remaining_balance + (float) ($loan->payment_credit ?? 0), 2));
    }

    private function baseAmount(Loan $loan): float
    {
        return max(0, round((float) ($loan->daily_payment ?: $loan->suggested_payment), 2));
    }

    private function date(Carbon|string|null $date): Carbon
    {
        return $date instanceof Carbon ? $date->copy()->startOfDay() : Carbon::parse($date ?? Carbon::today())->startOfDay();
    }
}
