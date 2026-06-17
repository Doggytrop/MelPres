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

        $periodsRequested = max(1, intval($data['periods'] ?? 1));
        $dailyPayment     = floatval($loan->daily_payment ?: $loan->suggested_payment);
        $amountPaid       = floatval($data['amount_paid']);
        $remaining        = $amountPaid;
        $penaltyPay       = 0;
        $interestPay      = 0;
        $capitalPay       = 0;

        // 1 — Mora primero
        if ($loan->accumulated_penalty > 0) {
            $penaltyPay = min($remaining, floatval($loan->accumulated_penalty));
            $remaining -= $penaltyPay;
            $loan->accumulated_penalty -= $penaltyPay;
        }

        // 2 — Interés pendiente
        if ($remaining > 0 && $loan->pending_interest > 0) {
            $interestPay = min($remaining, floatval($loan->pending_interest));
            $remaining  -= $interestPay;
            $loan->pending_interest -= $interestPay;
        }

        // 3 — El resto va a capital
        if ($remaining > 0) {
            $capitalPay = min($remaining, floatval($loan->remaining_balance));
            $loan->remaining_balance -= $capitalPay;
        }

        // 4 — Calcular sobrante (lo que pagó de más sobre los períodos seleccionados)
        $baseEsperado = round($dailyPayment * $periodsRequested, 2);
        $carryOver    = max(0, round($amountPaid - $baseEsperado, 2));

        // 5 — Tipo de pago
        $paymentType = $this->determinePaymentType($penaltyPay, $interestPay, $capitalPay);

        // 6 — Status
        if ($loan->remaining_balance <= 0 && $loan->pending_interest <= 0) {
            $loan->status            = 'paid';
            $loan->remaining_balance = 0;
            $loan->next_payment_date = null;
        } elseif ($loan->status === 'overdue' && $loan->accumulated_penalty <= 0) {
            $loan->status = 'active';
        }

        // 7 — Próxima fecha: avanza según períodos pagados
        if ($loan->status !== 'paid') {
            $loan->next_payment_date = $this->calculateNextPaymentMultiple(
                $data['payment_date'],
                $loan->payment_frequency,
                $periodsRequested
            );
        }

        $loan->save();

        app(\App\Services\ScoreService::class)->actualizar($loan->customer);

        \App\Models\ActivityLog::log(
            'payment', 'payments',
            'Registró pago por $' . number_format($amountPaid, 2) . ' en préstamo #' . $loan->id,
            $loan
        );

        // 8 — Crear pago
        $payment = Payment::create([
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
            'periods_covered'  => $periodsRequested,
            'carry_over'       => $carryOver,
        ]);

        $customer = $loan->customer;
        if ($customer?->phone) {
            app(\App\Services\WhatsAppService::class)
                ->sendPaymentConfirmation($customer, $loan, $payment);
        }

        return $payment;
    });
}

private function calculateNextPaymentMultiple(string $date, string $frequency, int $periods): string
{
    $carbon = \Carbon\Carbon::parse($date);

    for ($i = 0; $i < $periods; $i++) {
        $carbon = match ($frequency) {
            'daily'    => $carbon->addDay(),
            'weekly'   => $carbon->addWeek(),
            'biweekly' => $carbon->addDays(15),
            'monthly'  => $carbon->addMonth(),
            default    => $carbon->addMonth(),
        };
    }

    return $carbon->toDateString();
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

        return match ($frequency) {
            'daily'    => $carbon->addDay()->toDateString(),
            'weekly'   => $carbon->addWeek()->toDateString(),
            'biweekly' => $carbon->addDays(15)->toDateString(),
            'monthly'  => $carbon->addMonth()->toDateString(),
            default    => $carbon->addMonth()->toDateString(),
        };
    }
}