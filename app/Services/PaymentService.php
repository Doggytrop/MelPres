<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Loan;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function applyPayment(Loan $loan, array $data): Payment
    {
        return DB::transaction(function () use ($loan, $data) {

            $amountPaid = floatval($data['amount_paid']);
            $remaining  = $amountPaid;
            $penaltyPay = 0;
            $interestPay = 0;
            $capitalPay = 0;

            // 1 — Cubrir mora acumulada primero
            if ($loan->accumulated_penalty > 0) {
                $penaltyPay = min($remaining, floatval($loan->accumulated_penalty));
                $remaining -= $penaltyPay;
                $loan->accumulated_penalty -= $penaltyPay;
            }

            // 2 — Cubrir interés pendiente
            if ($remaining > 0 && $loan->pending_interest > 0) {
                $interestPay = min($remaining, floatval($loan->pending_interest));
                $remaining -= $interestPay;
                $loan->pending_interest -= $interestPay;
            }

            // 3 — El resto va a capital (remaining_balance)
            if ($remaining > 0) {
                $capitalPay = min($remaining, floatval($loan->remaining_balance));
                $loan->remaining_balance -= $capitalPay;
            }

            // 4 — Determinar tipo de pago
            $paymentType = $this->determinePaymentType($penaltyPay, $interestPay, $capitalPay);

            // 5 — Actualizar status si ya está saldado
            if ($loan->remaining_balance <= 0 && $loan->pending_interest <= 0) {
                $loan->status = 'paid';
                $loan->remaining_balance = 0;
                $loan->next_payment_date = null;
            } elseif ($loan->status === 'overdue' && $loan->accumulated_penalty <= 0) {
                $loan->status = 'active';
            }

            // 6 — Calcular próximo pago (solo si no está pagado)
            if ($loan->status !== 'paid') {
                $loan->next_payment_date = $this->calculateNextPayment(
                    $data['payment_date'],
                    $loan->payment_frequency
                );
            }

            $loan->save();

            // Actualizar score del cliente
            app(\App\Services\ScoreService::class)->actualizar($loan->customer);

            // 7 — Registrar movimiento
            return Payment::create([
                'loan_id'          => $loan->id,
                'amount_paid'      => $amountPaid,
                'penalty_payment'  => $penaltyPay,
                'interest_payment' => $interestPay,
                'capital_payment'  => $capitalPay,
                'payment_date'     => $data['payment_date'],
                'expected_date'    => $data['expected_date'] ?? null,
                'payment_type'     => $paymentType,
                'notes'            => $data['notes'] ?? null,
                'recorded_by'      => auth()->id(),
            ]);
        });
    }

    private function determinePaymentType(float $penalty, float $interest, float $capital): string
    {
        if ($penalty > 0 && $capital == 0 && $interest == 0) return 'penalty';
        if ($interest > 0 && $capital == 0)                  return 'interest_only';
        if ($capital > 0 && $interest == 0 && $penalty == 0) return 'capital';
        if ($capital > 0 && $interest > 0)                   return 'mixed';
        return 'partial';
    }

    private function calculateNextPayment(string $date, string $frequency): string
    {
        $carbon = \Carbon\Carbon::parse($date);

        return match($frequency) {
            'daily'    => $carbon->addDay()->toDateString(),
            'weekly'   => $carbon->addWeek()->toDateString(),
            'biweekly' => $carbon->addDays(15)->toDateString(),
            'monthly'  => $carbon->addMonth()->toDateString(),
            default    => $carbon->addMonth()->toDateString(),
        };
    }
}