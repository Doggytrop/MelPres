<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\CompanySubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanySubscriptionService
{
    public function __construct(private SaasAuditService $audit) {}

    public function suspend(Company $company): CompanySubscription
    {
        return $this->transition($company, 'company_suspended', [
            'status' => SubscriptionStatus::SUSPENDED,
            'suspended_at' => now(),
            'cancelled_at' => null,
        ], "Suscripción de {$company->name} suspendida.", [
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::PAST_DUE,
        ]);
    }

    public function reactivate(Company $company): CompanySubscription
    {
        return $this->transition($company, 'company_reactivated', [
            'status' => SubscriptionStatus::ACTIVE,
            'suspended_at' => null,
            'cancelled_at' => null,
        ], "Suscripción de {$company->name} reactivada.", [
            SubscriptionStatus::SUSPENDED,
            SubscriptionStatus::CANCELLED,
        ]);
    }

    public function cancel(Company $company): CompanySubscription
    {
        return $this->transition($company, 'company_cancelled', [
            'status' => SubscriptionStatus::CANCELLED,
            'suspended_at' => null,
            'cancelled_at' => now(),
        ], "Suscripción de {$company->name} cancelada.", [
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::PAST_DUE,
            SubscriptionStatus::SUSPENDED,
        ]);
    }

    public function renew(Company $company, int $years): CompanySubscription
    {
        return DB::transaction(function () use ($company, $years) {
            $subscription = $company->subscription()->lockForUpdate()->firstOrFail();
            $this->ensureAllowed($subscription, [
                SubscriptionStatus::ACTIVE,
                SubscriptionStatus::PAST_DUE,
                SubscriptionStatus::SUSPENDED,
            ]);
            $oldValues = $this->snapshot($subscription);
            $base = $subscription->current_period_end?->isFuture()
                ? $subscription->current_period_end->copy()
                : now();
            $periodEnd = $base->copy()->addYearsNoOverflow($years);

            $subscription->update([
                'status' => SubscriptionStatus::ACTIVE,
                'current_period_start' => $base,
                'current_period_end' => $periodEnd,
                'grace_until' => null,
                'suspended_at' => null,
                'cancelled_at' => null,
            ]);
            $subscription->refresh();

            $this->audit->record(
                'company_renewed',
                $company,
                ['subscription' => $oldValues],
                [
                    'subscription' => $this->snapshot($subscription),
                    'renewal' => ['years_added' => $years],
                ],
                "Suscripcion de {$company->name} renovada por {$years} ano(s)."
            );

            return $subscription;
        });
    }

    public function updateGrace(Company $company, mixed $graceUntil): CompanySubscription
    {
        return $this->transition($company, 'company_grace_updated', [
            'grace_until' => $graceUntil,
        ], "Periodo de gracia de {$company->name} actualizado.", [
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::PAST_DUE,
        ]);
    }

    public function removeGrace(Company $company): CompanySubscription
    {
        return $this->transition($company, 'company_grace_removed', [
            'grace_until' => null,
        ], "Periodo de gracia de {$company->name} eliminado.", [
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::PAST_DUE,
        ]);
    }

    private function transition(
        Company $company,
        string $action,
        array $attributes,
        string $description,
        array $allowedStatuses
    ): CompanySubscription {
        return DB::transaction(function () use ($company, $action, $attributes, $description, $allowedStatuses) {
            $subscription = $company->subscription()->lockForUpdate()->firstOrFail();
            $this->ensureAllowed($subscription, $allowedStatuses);
            $oldValues = $this->snapshot($subscription);

            $subscription->update($attributes);
            $subscription->refresh();

            $this->audit->record(
                $action,
                $company,
                ['subscription' => $oldValues],
                ['subscription' => $this->snapshot($subscription)],
                $description
            );

            return $subscription;
        });
    }

    private function ensureAllowed(
        CompanySubscription $subscription,
        array $allowedStatuses
    ): void {
        if (! in_array($subscription->effectiveStatus(), $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'subscription' => 'La acción no está disponible para el estado actual de la suscripción.',
            ]);
        }
    }

    private function snapshot(CompanySubscription $subscription): array
    {
        return [
            'status' => $subscription->status->value,
            'current_period_start' => $subscription->current_period_start?->toISOString(),
            'current_period_end' => $subscription->current_period_end?->toISOString(),
            'grace_until' => $subscription->grace_until?->toISOString(),
            'suspended_at' => $subscription->suspended_at?->toISOString(),
            'cancelled_at' => $subscription->cancelled_at?->toISOString(),
        ];
    }
}
