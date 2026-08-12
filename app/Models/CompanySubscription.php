<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySubscription extends Model
{
    protected $fillable = [
        'company_id',
        'status',
        'started_at',
        'current_period_start',
        'current_period_end',
        'grace_until',
        'suspended_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'started_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'grace_until' => 'datetime',
            'suspended_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function allowsAccess(): bool
    {
        return match ($this->effectiveStatus()) {
            SubscriptionStatus::ACTIVE => true,
            SubscriptionStatus::PAST_DUE => $this->grace_until !== null
                && $this->grace_until->greaterThanOrEqualTo(now()),
            SubscriptionStatus::SUSPENDED,
            SubscriptionStatus::CANCELLED => false,
        };
    }

    public function effectiveStatus(): SubscriptionStatus
    {
        if (in_array($this->status, [
            SubscriptionStatus::SUSPENDED,
            SubscriptionStatus::CANCELLED,
        ], true)) {
            return $this->status;
        }

        if ($this->status === SubscriptionStatus::PAST_DUE
            || $this->current_period_end?->isPast()) {
            return SubscriptionStatus::PAST_DUE;
        }

        return SubscriptionStatus::ACTIVE;
    }

    public function scopeEffectivelyActive(Builder $query): Builder
    {
        return $query
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->where(function (Builder $query): void {
                $query->whereNull('current_period_end')
                    ->orWhere('current_period_end', '>=', now());
            });
    }

    public function scopeEffectivelyPastDue(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('status', SubscriptionStatus::PAST_DUE->value)
                ->orWhere(function (Builder $query): void {
                    $query->where('status', SubscriptionStatus::ACTIVE->value)
                        ->whereNotNull('current_period_end')
                        ->where('current_period_end', '<', now());
                });
        });
    }
}
