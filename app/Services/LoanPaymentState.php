<?php

namespace App\Services;

final class LoanPaymentState
{
    public function __construct(
        public readonly float $baseAmount,
        public readonly string $frequency,
        public readonly ?string $oldestPendingDate,
        public readonly float $currentPeriodBalance,
        public readonly int $duePeriods,
        public readonly int $overduePeriods,
        public readonly int $gracePeriods,
        public readonly float $overdueAmount,
        public readonly float $dueAmount,
        public readonly float $paymentCredit,
        public readonly float $nextEffectiveAmount,
        public readonly float $amountToCurrent,
        public readonly bool $installmentSchedule,
    ) {}
}
