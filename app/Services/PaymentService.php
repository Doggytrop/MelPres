<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private readonly LoanPaymentStateService $paymentState) {}

    public function applyPayment(Loan $loan, array $data): Payment
    {
        $companyId = app(CompanyContext::class)->getCompanyId();

        if (! $companyId || ! $loan->company_id || (int) $loan->company_id !== (int) $companyId) {
            abort(403, 'El préstamo no pertenece a la empresa activa.');
        }

        return DB::transaction(function () use ($loan, $data, $companyId) {
            $loan = Loan::where('loans.id', $loan->getKey())
                ->where('loans.company_id', $companyId)
                ->lockForUpdate()
                ->firstOrFail();

            $customer = Customer::where('customers.id', $loan->customer_id)
                ->where('customers.company_id', $companyId)
                ->firstOrFail();

            $amountPaid = round((float) $data['amount_paid'], 2);
            $stateBefore = $this->paymentState->state($loan, $data['payment_date']);

            if ($amountPaid <= 0 && ($stateBefore->paymentCredit <= 0 || $stateBefore->dueAmount <= 0)) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'El monto debe ser mayor a cero salvo que exista crédito aplicable.',
                ]);
            }

            $allocation = $this->paymentState->simulate(
                $loan,
                $amountPaid,
                $data['payment_date'],
                $data['selected_through_date'] ?? null,
                (bool) ($data['authorize_future_periods'] ?? false),
            );

            $loan->accumulated_penalty = max(0, round((float) $loan->accumulated_penalty - $allocation->penaltyPayment, 2));
            $loan->pending_interest = max(0, round((float) $loan->pending_interest - $allocation->interestPayment, 2));
            $contractReduction = $loan->type === 'interest'
                ? $allocation->capitalPayment
                : $allocation->capitalPayment + $allocation->interestPayment;

            $loan->remaining_balance = max(0, round(
                (float) $loan->remaining_balance - $contractReduction,
                2
            ));
            $loan->current_period_balance = $allocation->currentPeriodBalance;
            $loan->payment_credit = $allocation->paymentCredit;
            $loan->next_payment_date = $allocation->nextPaymentDate;

            if ($loan->remaining_balance <= 0 && $loan->pending_interest <= 0) {
                $loan->status = 'paid';
                $loan->remaining_balance = 0;
                $loan->next_payment_date = null;
                $loan->current_period_balance = 0;
            } else {
                $stateAfter = $this->paymentState->state($loan, $data['payment_date']);
                $loan->status = $stateAfter->overduePeriods > 0 || $loan->accumulated_penalty > 0
                    ? 'overdue'
                    : 'active';
            }

            $loan->save();

            $payment = Payment::create([
                'company_id' => $companyId,
                'loan_id' => $loan->id,
                'amount_paid' => $amountPaid,
                'penalty_payment' => $allocation->penaltyPayment,
                'interest_payment' => $allocation->interestPayment,
                'capital_payment' => $allocation->capitalPayment,
                'periodic_amount_applied' => $allocation->periodicAmountApplied,
                'payment_date' => $data['payment_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'payment_type' => $this->determinePaymentType($allocation->penaltyPayment, $allocation->interestPayment, $allocation->capitalPayment),
                'notes' => $data['notes'] ?? null,
                'recorded_by' => auth()->id(),
                'periods_covered' => $allocation->periodsCovered,
                'carry_over' => $allocation->creditGenerated,
                'credit_generated' => $allocation->creditGenerated,
                'credit_consumed' => $allocation->creditConsumed,
            ]);

            \App\Models\ActivityLog::log(
                'payment',
                'payments',
                'Registró pago por $'.number_format($amountPaid, 2).' en préstamo #'.$loan->id,
                $loan
            );

            if ((int) $payment->company_id !== (int) $companyId) {
                abort(409, 'El pago no pertenece a la empresa activa.');
            }

            app(ScoreService::class)->actualizar($customer);

            if ($customer->phone) {
                app(WhatsAppService::class)->sendPaymentConfirmation($customer, $loan, $payment);
            }

            return $payment;
        });
    }

    private function determinePaymentType(float $penalty, float $interest, float $capital): string
    {
        if ($penalty > 0 && $capital == 0 && $interest == 0) return 'penalty';
        if ($interest > 0 && $capital == 0) return 'interest_only';
        if ($capital > 0 && $interest == 0 && $penalty == 0) return 'capital';
        if ($capital > 0 && ($interest > 0 || $penalty > 0)) return 'mixed';

        return 'partial';
    }
}
