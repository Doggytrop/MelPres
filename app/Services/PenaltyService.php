<?php

namespace App\Services;

use App\Models\loan;
use Carbon\Carbon;

class PenaltyService
{
    public function procesarMora(loan $loan): void
    {
        // Si no tiene mora configurada, no hacer nada
        if (!$loan->penalty_type || !$loan->penalty_value) return;

        // Si no tiene fecha de próximo payment, no hacer nada
        if (!$loan->next_payment_date) return;

        $hoy          = Carbon::today();
        $fechaEsperada = Carbon::parse($loan->next_payment_date);
        $diasGracia   = $loan->grace_days ?? 0;

        // Fecha límite = fecha esperada + días de gracia
        $fechaLimite = $fechaEsperada->copy()->addDays($diasGracia);

        // Si aún está dentro del periodo de gracia, no hacer nada
        if ($hoy->lte($fechaLimite)) return;

        // — Fuera del periodo de gracia → marcar como overdue —
        if ($loan->status === 'active') {
            $loan->status = 'overdue';
        }

        // — Calcular mora según tipo —
        if ($loan->penalty_type === 'fixed') {
            $this->aplicarMorafixed($loan, $hoy, $fechaLimite);
        } else if ($loan->penalty_type === 'percentage') {
            $this->aplicarMorapercentage($loan, $hoy, $fechaLimite);
        }

        $loan->save();
    }

    // — Mora fixed: $X por cada día desde que venció la gracia —
    private function aplicarMorafixed(loan $loan, Carbon $hoy, Carbon $fechaLimite): void
    {
        // Solo cobra el día de hoy si es el primer día fuera de gracia
        // o si no se ha cobrado mora hoy todavía
        $diasAtraso = $fechaLimite->diffInDays($hoy);

        // El tope es el siguiente periodo — calculamos cuántos días tiene el periodo
        $diasPeriodo = $this->diasPorFrecuencia($loan->payment_frequency);

        // No cobrar más allá del periodo complete
        $diasACobrar = min($diasAtraso, $diasPeriodo);

        // Solo acumular el día de hoy (el comando corre diario)
        // Para evitar duplicar, solo sumamos $penalty_value una vez por ejecución
        if ($diasACobrar > 0) {
            $loan->accumulated_penalty += floatval($loan->penalty_value);
        }
    }

    // — Mora percentage: X% del saldo por cada periodo overdue complete —
    private function aplicarMorapercentage(loan $loan, Carbon $hoy, Carbon $fechaLimite): void
    {
        // Verificar si hoy es exactamente el día que vence (fechaLimite + 1)
        // Solo cobrar una vez por periodo overdue
        $primerDiaoverdue = $fechaLimite->copy()->addDay();

        // Solo cobrar si hoy es el primer día fuera de gracia del periodo actual
        if ($hoy->isSameDay($primerDiaoverdue)) {
            $mora = floatval($loan->remaining_balance) * (floatval($loan->penalty_value) / 100);
            $loan->accumulated_penalty += round($mora, 2);
        }
    }

    private function diasPorFrecuencia(string $frecuencia): int
    {
        return match($frecuencia) {
            'weekly'   => 7,
            'biweekly' => 15,
            'monthly'   => 30,
            default     => 30,
        };
    }
}