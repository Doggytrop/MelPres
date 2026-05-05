<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Loan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'type',
        'payment_frequency',
        'number_of_periods',
        'original_amount',
        'remaining_balance',
        'interest_rate',
        'accrued_interest',
        'pending_interest',
        'daily_payment',
        'penalty_type',
        'penalty_value',
        'grace_days',
        'accumulated_penalty',
        'start_date',
        'due_date',
        'next_payment_date',
        'status',
        'restructured',
        'notes',
    ];

    protected $casts = [
        'start_date'          => 'date',
        'due_date'            => 'date',
        'next_payment_date'   => 'date',
        'original_amount'     => 'decimal:2',
        'remaining_balance'   => 'decimal:2',
        'interest_rate'       => 'decimal:2',
        'accrued_interest'    => 'decimal:2',
        'pending_interest'    => 'decimal:2',
        'daily_payment'       => 'decimal:2',
        'accumulated_penalty' => 'decimal:2',
    ];

    // — Relationships —
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function restructurings()
    {
        return $this->hasMany(Restructuring::class, 'original_loan_id');
    }

    // — Accessors —
    public function getMonthlyInterestAttribute(): float
    {
        return round($this->original_amount * ($this->interest_rate / 100), 2);
    }

    public function getSuggestedPaymentAttribute(): float
    {
        if ($this->type === 'daily') {
            return $this->daily_payment ?? 0;
        }

        if (!$this->number_of_periods || $this->number_of_periods == 0) return 0;
        return round($this->remaining_balance / $this->number_of_periods, 2);
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'interest' => 'Interés',
            'term'     => 'Plazo',
            'daily'    => 'Diario',
        };
    }

    public function getFrequencyLabelAttribute(): string
    {
        return match($this->payment_frequency) {
            'weekly'   => 'Semanal',
            'biweekly' => 'Quincenal',
            'monthly'  => 'Mensual',
            'daily'    => 'Diario',
        };
    }

    public function isOverdue(): bool
    {
        if (!$this->next_payment_date) return false;
        return now()->gt($this->next_payment_date->addDays($this->grace_days ?? 0));
    }
}