<?php

namespace App\Support;

use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\CompanySubscription;
use Illuminate\Database\Eloquent\Model;

final class SaasPresentation
{
    public static function companyStatus(string $status): array
    {
        return match ($status) {
            'active' => ['label' => 'Activa', 'tone' => 'success'],
            'inactive' => ['label' => 'Inactiva', 'tone' => 'muted'],
            default => ['label' => ucfirst($status), 'tone' => 'muted'],
        };
    }

    public static function subscriptionStatus(
        CompanySubscription|SubscriptionStatus|string|null $status
    ): array
    {
        if ($status instanceof CompanySubscription) {
            $status = $status->effectiveStatus();
        }

        $value = $status instanceof SubscriptionStatus ? $status->value : $status;

        return match ($value) {
            'active' => ['label' => 'Activa', 'tone' => 'success'],
            'past_due' => ['label' => 'Pago pendiente', 'tone' => 'warning'],
            'suspended' => ['label' => 'Suspendida', 'tone' => 'danger'],
            'cancelled' => ['label' => 'Cancelada', 'tone' => 'dark'],
            default => ['label' => 'Sin suscripción', 'tone' => 'muted'],
        };
    }

    public static function actionLabel(string $action): string
    {
        return match ($action) {
            'company_created' => 'Empresa creada',
            'company_updated' => 'Empresa actualizada',
            'company_suspended' => 'Empresa suspendida',
            'company_reactivated' => 'Empresa reactivada',
            'company_marked_past_due' => 'Marcada con pago pendiente',
            'company_cancelled' => 'Suscripción cancelada',
            'company_renewed' => 'Suscripción renovada',
            'company_grace_updated' => 'Periodo de gracia actualizado',
            'company_grace_removed' => 'Periodo de gracia eliminado',
            default => $action,
        };
    }

    public static function actionTone(string $action): string
    {
        return match ($action) {
            'company_created',
            'company_reactivated',
            'company_renewed' => 'success',
            'company_grace_updated',
            'company_grace_removed' => 'muted',
            'company_suspended',
            'company_marked_past_due' => 'warning',
            'company_cancelled' => 'danger',
            default => 'muted',
        };
    }

    public static function subjectLabel(?string $subjectType): string
    {
        return $subjectType === Company::class ? 'Empresa' : 'Sin sujeto';
    }

    public static function subjectName(?Model $subject): ?string
    {
        return $subject instanceof Company ? $subject->name : null;
    }

    public static function renewalTiming(?CompanySubscription $subscription): string
    {
        $renewal = $subscription?->current_period_end;

        if (! $renewal) {
            return 'Sin vencimiento';
        }

        $days = (int) now()->startOfDay()->diffInDays($renewal->copy()->startOfDay());

        if ($renewal->isToday()) {
            return 'Vence hoy';
        }

        return $renewal->isFuture()
            ? "Vence en {$days} ".($days === 1 ? 'día' : 'días')
            : "Venció hace {$days} ".($days === 1 ? 'día' : 'días');
    }

    public static function graceStatus(?CompanySubscription $subscription): string
    {
        if (! $subscription?->grace_until) {
            return 'Sin periodo de gracia';
        }

        return $subscription->grace_until->greaterThanOrEqualTo(now())
            ? 'Vigente'
            : 'Vencida';
    }

    public static function contractedYears(?CompanySubscription $subscription): ?int
    {
        if (! $subscription?->current_period_start || ! $subscription->current_period_end) {
            return null;
        }

        $months = $subscription->current_period_start->diffInMonths(
            $subscription->current_period_end
        );

        return max(1, (int) round($months / 12));
    }
}
