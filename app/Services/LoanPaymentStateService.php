<?php

namespace App\Services;

use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class LoanPaymentStateService
{
    public function state(Loan $loan, Carbon|string|null $asOf = null): LoanPaymentState
    {
        $date = $this->date($asOf);
        $base = $this->baseAmount($loan);
        $scheduled = $this->supportsInstallmentSchedule($loan);
        $oldest = $loan->next_payment_date?->copy()->startOfDay();
        $credit = max(0, round((float) ($loan->payment_credit ?? 0), 2));

        if (! $oldest || $loan->status === 'paid' || $base <= 0) {
            return new LoanPaymentState($base, $loan->payment_frequency, null, 0, 0, 0, 0, 0, 0, $credit, 0, 0, $scheduled);
        }

        if ($scheduled) {
            $base = $this->periodAmountForDate($loan, $oldest);
        }

        $current = $this->currentBalance($loan, $base);

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
        $dueAmount = 0;

        foreach ($dates as $index => $dueDate) {
            $periodAmount = $index === 0
                ? $current
                : $this->periodAmountForDate($loan, $dueDate);

            $dueAmount += $periodAmount;

            if ($date->gt($dueDate->copy()->addDays((int) ($loan->grace_days ?? 0)))) {
                $overduePeriods++;
                $overdueAmount += $periodAmount;
            }
        }

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

    public function simulate(
        Loan $loan,
        float $amountReceived,
        Carbon|string|null $asOf = null,
        Carbon|string|null $selectedThroughDate = null,
        bool $authorizeFuturePeriods = false,
    ): PaymentAllocation
    {
        $date = $this->date($asOf);
        $state = $this->state($loan, $date);
        $cash = round($amountReceived, 2);
        $penalty = min($cash, max(0, (float) $loan->accumulated_penalty));
        $cash = round($cash - $penalty, 2);

        if ($loan->type === 'interest') {
            return $this->simulateInterestLoan($loan, $state, $date, $penalty, $cash);
        }

        // pending_interest conserva compatibilidad con intereses no financiados que
        // ya estuvieran explícitamente pendientes. El interés contractual de daily
        // y term se distribuye abajo, no se copia aquí.
        $pendingInterest = min($cash, max(0, (float) $loan->pending_interest));
        $cashForContract = round($cash - $pendingInterest, 2);
        [$contractInterest, $capital] = $this->allocateFinancedContract($loan, $state, $cashForContract);
        $interest = round($pendingInterest + $contractInterest, 2);

        $coverageThrough = $date;
        $coverageAmount = $state->dueAmount;

        if ($selectedThroughDate !== null) {
            [$coverageThrough, $coverageAmount] = $this->selectedCoverageRange($loan, $state, $selectedThroughDate);
        } elseif ($authorizeFuturePeriods && $state->installmentSchedule && $state->oldestPendingDate) {
            [$coverageThrough, $coverageAmount] = $this->automaticAdminCoverageRange($loan, $state, $cashForContract);
        }

        $creditConsumed = min($state->paymentCredit, $coverageAmount);
        $periodicCash = min($cashForContract, max(0, $coverageAmount - $creditConsumed));
        $periodicApplied = round($creditConsumed + $periodicCash, 2);
        $creditGenerated = max(0, round($cashForContract - $periodicCash, 2));
        $newCredit = max(0, round($state->paymentCredit - $creditConsumed + $creditGenerated, 2));

        [$covered, $nextDate, $currentBalance] = $this->advanceOpenObligation($loan, $state, $periodicApplied, $coverageThrough);

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

    private function simulateInterestLoan(Loan $loan, LoanPaymentState $state, Carbon $date, float $penalty, float $cash): PaymentAllocation
    {
        $interestDue = max(0, $state->currentPeriodBalance);
        $remainingCapital = max(0, (float) $loan->remaining_balance);
        $isLiquidation = $cash >= round($interestDue + $remainingCapital, 2);

        if ($isLiquidation) {
            $interest = $interestDue;
            $capital = $remainingCapital;
            $periodic = $interestDue;
            $creditGenerated = max(0, round($cash - $interest - $capital, 2));

            return new PaymentAllocation(
                $penalty,
                $interest,
                $capital,
                $periodic,
                $periodic,
                $creditGenerated,
                0,
                $creditGenerated,
                0,
                1,
                null,
            );
        }

        // Un préstamo renovable de interés nunca amortiza capital con un pago
        // ordinario: cualquier efectivo recibido se registra como interés.
        $interest = $cash;
        $periodic = min($cash, $interestDue);
        $currentBalance = max(0, round($interestDue - $periodic, 2));
        $covered = $currentBalance <= 0 && $interestDue > 0 ? 1 : 0;
        $next = $covered
            ? $this->addPeriods($date, $loan->payment_frequency)->toDateString()
            : $state->oldestPendingDate;

        return new PaymentAllocation(
            $penalty,
            $interest,
            0,
            $periodic,
            $periodic,
            0,
            0,
            0,
            $currentBalance,
            $covered,
            $next,
        );
    }

    /**
     * Distribuye el efectivo contractual sobre las obligaciones desde la más
     * antigua. Los centavos sobrantes se asignan a los primeros períodos, por
     * lo que las sumas finales siempre coinciden exactamente con el contrato.
     * El crédito ya generado ocupa primero la siguiente obligación, pero no
     * vuelve a reducir remaining_balance cuando después se consume.
     *
     * @return array{0: float, 1: float}
     */
    private function allocateFinancedContract(Loan $loan, LoanPaymentState $state, float $cash): array
    {
        if ($cash <= 0) {
            return [0, 0];
        }

        $periods = max(1, (int) ($loan->number_of_periods ?? 1));
        $index = $this->periodIndex($loan, $state->oldestPendingDate);
        $currentTotal = $this->periodAmountCents($loan, $index, $periods);
        $alreadyApplied = max(0, $currentTotal - $this->cents($state->currentPeriodBalance));
        $skip = $alreadyApplied + $this->cents($state->paymentCredit);
        $remaining = min($this->cents($cash), $this->cents($loan->remaining_balance));
        $interest = 0;
        $capital = 0;

        for ($period = $index; $period < $periods && $remaining > 0; $period++) {
            $periodTotal = $this->periodAmountCents($loan, $period, $periods);
            $periodInterest = $this->interestAmountCents($loan, $period, $periods);
            $periodCapital = $periodTotal - $periodInterest;

            $alreadyInPeriod = min($skip, $periodTotal);
            $skip = max(0, $skip - $alreadyInPeriod);
            $available = $periodTotal - $alreadyInPeriod;
            $applied = min($remaining, $available);
            $interestOutstanding = max(0, $periodInterest - $alreadyInPeriod);
            $interestApplied = min($applied, $interestOutstanding);

            $interest += $interestApplied;
            $capital += $applied - $interestApplied;
            $remaining -= $applied;
        }

        return [$interest / 100, $capital / 100];
    }

    private function periodIndex(Loan $loan, ?string $date): int
    {
        if (! $date || ! $loan->start_date) {
            return 0;
        }

        $target = Carbon::parse($date)->startOfDay();
        $periods = max(1, (int) ($loan->number_of_periods ?? 1));
        $cursor = $this->addPeriods($loan->start_date, $loan->payment_frequency);

        for ($index = 0; $index < $periods; $index++) {
            if ($cursor->isSameDay($target)) {
                return $index;
            }

            $cursor = $this->addPeriods($cursor, $loan->payment_frequency);
        }

        return 0;
    }

    private function periodAmountCents(Loan $loan, int $period, int $periods): int
    {
        return $this->distributedCents(
            $this->cents((float) $loan->original_amount + (float) $loan->accrued_interest),
            $period,
            $periods,
        );
    }

    private function periodAmountForDate(Loan $loan, Carbon|string $date): float
    {
        $periods = max(1, (int) ($loan->number_of_periods ?? 1));

        return $this->periodAmountCents($loan, $this->periodIndex($loan, (string) $date), $periods) / 100;
    }

    private function interestAmountCents(Loan $loan, int $period, int $periods): int
    {
        return $this->distributedCents($this->cents($loan->accrued_interest), $period, $periods);
    }

    private function distributedCents(int $total, int $period, int $periods): int
    {
        $base = intdiv($total, $periods);
        $remainder = $total % $periods;

        return $base + ($period < $remainder ? 1 : 0);
    }

    private function cents(float|string|null $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    /**
     * Returns the server-validated, continuous range an administrator selected.
     * The payment date remains the sole source for delinquency calculations;
     * this range only authorizes applying cash to future contractual periods.
     *
     * @return array{0: Carbon, 1: float}
     */
    private function selectedCoverageRange(Loan $loan, LoanPaymentState $state, Carbon|string $selectedThroughDate): array
    {
        if (! $state->installmentSchedule || ! $state->oldestPendingDate) {
            throw ValidationException::withMessages([
                'selected_through_date' => 'El préstamo no admite selección de períodos.',
            ]);
        }

        $selectedThrough = $this->date($selectedThroughDate);
        $date = Carbon::parse($state->oldestPendingDate)->startOfDay();

        if ($selectedThrough->lt($date)) {
            throw ValidationException::withMessages([
                'selected_through_date' => 'La fecha seleccionada no puede ser anterior a la siguiente obligación.',
            ]);
        }

        $amount = 0.0;
        $guard = max(1, (int) ($loan->number_of_periods ?? 365));

        for ($period = 0; $period < $guard && $this->isWithinContract($loan, $date); $period++) {
            $amount = round($amount + ($period === 0
                ? $state->currentPeriodBalance
                : min($this->periodAmountForDate($loan, $date), $this->periodicCapacity($loan))), 2);

            if ($date->isSameDay($selectedThrough)) {
                return [$selectedThrough, $amount];
            }

            $date = $this->addPeriods($date, $loan->payment_frequency);
        }

        throw ValidationException::withMessages([
            'selected_through_date' => 'La fecha seleccionada no pertenece al calendario del préstamo.',
        ]);
    }

    /**
     * Authorizes consecutive future periods for the administrative workflow
     * only. The range is derived from server-side cash and the real schedule,
     * never from a client-provided period count.
     *
     * @return array{0: Carbon, 1: float}
     */
    private function automaticAdminCoverageRange(Loan $loan, LoanPaymentState $state, float $cashForContract): array
    {
        $available = round(max(0, $cashForContract) + $state->paymentCredit, 2);

        if ($available <= 0) {
            return [Carbon::parse($state->oldestPendingDate), 0.0];
        }

        $date = Carbon::parse($state->oldestPendingDate)->startOfDay();
        $lastDate = $date->copy();
        $amount = 0.0;
        $guard = max(1, (int) ($loan->number_of_periods ?? 365));

        for ($period = 0; $period < $guard && $this->isWithinContract($loan, $date); $period++) {
            $lastDate = $date->copy();
            $amount = round($amount + ($period === 0
                ? $state->currentPeriodBalance
                : min($this->periodAmountForDate($loan, $date), $this->periodicCapacity($loan))), 2);

            if ($amount >= $available) {
                return [$date, $amount];
            }

            $date = $this->addPeriods($date, $loan->payment_frequency);
        }

        return [$lastDate, $amount];
    }

    private function advanceOpenObligation(Loan $loan, LoanPaymentState $state, float $applied, Carbon $coverageThrough): array
    {
        if ($applied <= 0) {
            return [0, $state->oldestPendingDate, $state->currentPeriodBalance];
        }

        $remainingApplied = $applied;
        $balance = $state->currentPeriodBalance;
        $date = Carbon::parse($state->oldestPendingDate);
        $covered = 0;

        while ($date->lte($coverageThrough) && $remainingApplied >= $balance && $balance > 0) {
            $remainingApplied = round($remainingApplied - $balance, 2);
            $covered++;
            $date = $this->addPeriods($date, $loan->payment_frequency);

            if (! $this->isWithinContract($loan, $date)) {
                return [$covered, null, 0];
            }

            $balance = min($this->periodAmountForDate($loan, $date), $this->periodicCapacity($loan));
        }

        if ($date->lte($coverageThrough) && $remainingApplied > 0) {
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
