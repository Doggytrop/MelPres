<?php

namespace App\Services;

use App\Models\Loan;
use Carbon\Carbon;

class PenaltyService
{
    public function processLoan(Loan $loan): void
    {
        if (!$loan->penalty_type || !$loan->penalty_value) return;
        if (!$loan->next_payment_date) return;

        $today    = Carbon::today();
        $graceEnd = Carbon::parse($loan->next_payment_date)->addDays($loan->grace_days ?? 0);

        // Dentro del periodo de gracia → nada
        if ($today->lte($graceEnd)) return;

        // Marcar como vencido
        if ($loan->status === 'active') {
            $loan->status = 'overdue';
        }

        // Evitar cobrar dos veces el mismo día
        if ($loan->penalty_last_applied_date &&
            Carbon::parse($loan->penalty_last_applied_date)->isSameDay($today)) {
            $loan->save();
            return;
        }

        $daysOverdue = $graceEnd->diffInDays($today); // días desde que venció la gracia

        match ($loan->penalty_type) {
            'fixed'              => $this->applyFixed($loan, $daysOverdue),
            'percentage_period'  => $this->applyPercentagePeriod($loan, $daysOverdue),
            'percentage_balance' => $this->applyPercentageBalance($loan, $daysOverdue),
            default              => null,
        };

        $loan->penalty_last_applied_date = $today->toDateString();
        $loan->save();
    }

    /**
     * Monto fijo: $X por día de atraso, hasta un máximo de diasPeriodo días.
     * Después del límite, deja de cobrar.
     */
    private function applyFixed(Loan $loan, int $daysOverdue): void
    {
        $periodDays = $this->periodDays($loan->payment_frequency);

        // Para frecuencia diaria el tope sería 1, lo que no tiene sentido.
        // Usamos número de periodos del préstamo como tope máximo.
        $maxDays = $periodDays === 1
            ? ($loan->number_of_periods ?? 30)
            : $periodDays;

        if ($daysOverdue >= 1 && $daysOverdue <= $maxDays) {
            $loan->accumulated_penalty += floatval($loan->penalty_value);
        }
    }

    /**
     * Porcentaje sobre saldo restante: X% del remaining_balance,
     * cobrado una vez por periodo de atraso.
     */
    private function applyPercentagePeriod(Loan $loan, int $daysOverdue): void
    {
        $periodDays = $this->periodDays($loan->payment_frequency);

        // Cobra el día 1, luego cada periodDays días (día 1, 8, 15... para semanal)
        if (($daysOverdue - 1) % $periodDays === 0) {
            $penalty = floatval($loan->remaining_balance) * (floatval($loan->penalty_value) / 100);
            $loan->accumulated_penalty += round($penalty, 2);
        }
    }

    /**
     * Porcentaje sobre saldo total original: X% del original_amount,
     * cobrado una vez por periodo de atraso.
     */
    private function applyPercentageBalance(Loan $loan, int $daysOverdue): void
    {
        $periodDays = $this->periodDays($loan->payment_frequency);

        if (($daysOverdue - 1) % $periodDays === 0) {
            $penalty = floatval($loan->original_amount) * (floatval($loan->penalty_value) / 100);
            $loan->accumulated_penalty += round($penalty, 2);
        }
    }

    private function periodDays(string $frequency): int
    {
        return match ($frequency) {
            'daily'    => 1,
            'weekly'   => 7,
            'biweekly' => 15,
            'monthly'  => 30,
            default    => 30,
        };
    }
}