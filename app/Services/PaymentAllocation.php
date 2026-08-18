<?php

namespace App\Services;

final class PaymentAllocation
{
    public function __construct(
        public readonly float $penaltyPayment,
        public readonly float $interestPayment,
        public readonly float $capitalPayment,
        public readonly float $periodicCashApplied,
        public readonly float $periodicAmountApplied,
        public readonly float $creditGenerated,
        public readonly float $creditConsumed,
        public readonly float $paymentCredit,
        public readonly float $currentPeriodBalance,
        public readonly int $periodsCovered,
        public readonly ?string $nextPaymentDate,
    ) {}
}
