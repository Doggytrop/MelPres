<?php

namespace App\Services;

use App\Models\payment;
use App\Models\loan;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function aplicarpayment(loan $loan, array $datos): payment
    {
        return DB::transaction(function () use ($loan, $datos) {

            $montopaid  = floatval($datos['amount_paid']);
            $restante     = $montopaid;
            $abonoMora    = 0;
            $abonointerest = 0;
            $abonoCapital = 0;

            // 1 — Cubrir mora acumulada primero
            if ($loan->accumulated_penalty > 0) {
                $abonoMora                = min($restante, floatval($loan->accumulated_penalty));
                $restante                -= $abonoMora;
                $loan->accumulated_penalty -= $abonoMora;
            }

            // 2 — Cubrir interés pendiente (solo si hay saldo pendiente de interés)
            if ($restante > 0 && $loan->pending_interestt > 0) {
                $abonointerest                 = min($restante, floatval($loan->pending_interestt));
                $restante                    -= $abonointerest;
                $loan->pending_interestt -= $abonointerest;
            }

            // 3 — El resto va a capital
            if ($restante > 0) {
                $abonoCapital              = min($restante, floatval($loan->remaining_balance));
                $loan->remaining_balance -= $abonoCapital;
            }

            // 4 — Determinar tipo de payment
            $tipopayment = $this->determinarTipopayment($abonoMora, $abonointerest, $abonoCapital);

            // 5 — Actualizar status si ya está saldado
            if ($loan->remaining_balance <= 0 && $loan->pending_interestt <= 0) {
                $loan->status         = 'paid';
                $loan->remaining_balance = 0;
            } elseif ($loan->status === 'overdue' && $loan->accumulated_penalty <= 0) {
                $loan->status = 'active';
            }

            // 6 — Calcular próximo payment
            $loan->next_payment_date = $this->calcularProximopayment(
                $datos['payment_date'],
                $loan->payment_frequency
            );

            $loan->save();
            // Actualizar score del customer
        app(\App\Services\ScoreService::class)->actualizar($loan->customer);

            // 7 — Registrar movimiento
            return payment::create([
                'loan_id'    => $loan->id,
                'amount_paid'   => $montopaid,
                'penalty_payment'     => $abonoMora,
                'interestt_payment'  => $abonointerest,
                'capital_payment'  => $abonoCapital,
                'payment_date'     => $datos['payment_date'],
                'expected_date' => $datos['expected_date'] ?? null,
                'payment_type'      => $tipopayment,
                'observaciones'  => $datos['observaciones'] ?? null,
                'recorded_by' => auth()->id(),
            ]);
        });
    }

    private function determinarTipopayment(float $mora, float $interest, float $capital): string
    {
        if ($mora > 0 && $capital == 0 && $interest == 0) return 'mora';
        if ($interest > 0 && $capital == 0)               return 'interest_onlyt';
        if ($capital > 0 && $interest == 0)               return 'capital';
        if ($capital > 0 && $interest > 0)                return 'mixed';
        return 'partial';
    }

    private function calcularProximopayment(string $fechapayment, string $frecuencia): string
    {
        $fecha = \Carbon\Carbon::parse($fechapayment);

        return match($frecuencia) {
            'weekly'   => $fecha->addWeek()->toDateString(),
            'biweekly' => $fecha->addDays(15)->toDateString(),
            'monthly'   => $fecha->addMonth()->toDateString(),
            default     => $fecha->addMonth()->toDateString(),
        };
    }
}