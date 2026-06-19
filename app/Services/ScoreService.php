<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Loan;

class ScoreService
{
    public function calcular(Customer $customer): int
    {
        $score = 100;

        $loans = Loan::where('customer_id', $customer->id)
                     ->with('payments')
                     ->get();

        foreach ($loans as $loan) {

            // — Préstamo paid complete —
            if ($loan->status === 'paid') {
                $score += 20;
            }

            // — Préstamo overdue actualmente —
            if ($loan->status === 'overdue') {
                $score -= 20;
            }

            // — Reestructuración activa —
            if ($loan->restructured && in_array($loan->status, ['active', 'overdue'])) {
                $score -= 15;
            }

            // — Reestructuración completada —
            if ($loan->restructured && $loan->status === 'paid') {
                $score += 10;
            }

            foreach ($loan->payments as $payment) {

                // — Pago puntual (sin mora) —
                if ($payment->penalty_payment == 0) {
                    $score += 5;
                }

                // — Pago con mora (se atrasó) —
                if ($payment->penalty_payment > 0) {
                    $score -= 10;
                }
            }
        }

        // El score nunca baja de 0 ni sube de 1000
        return max(0, min(1000, $score));
    }

    public function actualizar(Customer $customer): void
    {
        $score = $this->calcular($customer);

        $customer->score            = $score;
        $customer->score_updated_at = now();
        $customer->save();
    }

    public function etiqueta(int $score): array
    {
        return match(true) {
            $score >= 80 => ['label' => 'Excelente',   'color' => '#1f6b21', 'bg' => '#e8f5e9'],
            $score >= 60 => ['label' => 'Bueno',        'color' => '#1565c0', 'bg' => '#e3f2fd'],
            $score >= 40 => ['label' => 'Regular',      'color' => '#e65100', 'bg' => '#fff3e0'],
            default      => ['label' => 'Alto riesgo',  'color' => '#c0392b', 'bg' => '#fdecea'],
        };
    }
}