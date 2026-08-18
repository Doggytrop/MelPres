<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Loan;
class Payment extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'loan_id',
        'amount_paid',
        'penalty_payment',
        'interest_payment',
        'capital_payment',
        'periodic_amount_applied',
        'payment_date',
        'expected_date',
        'payment_type',
        'notes',
        'recorded_by',
        'periods_covered',
        'carry_over',
        'credit_generated',
        'credit_consumed',
    ];

    protected $casts = [
        'payment_date'     => 'date',
        'expected_date' => 'date',
        'amount_paid'   => 'decimal:2',
        'penalty_payment'     => 'decimal:2',
        'interest_payment'  => 'decimal:2',
        'capital_payment'  => 'decimal:2',
        'periodic_amount_applied' => 'decimal:2',
        'carry_over' => 'decimal:2',
        'credit_generated' => 'decimal:2',
        'credit_consumed' => 'decimal:2',
    ];

    // — Relaciones —
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getPaymentTypeLabelAttribute(): string
    {
        return match($this->payment_type) {
            'penalty'       => 'Mora',
            'interest_only' => 'Solo interés',
            'capital'       => 'Capital',
            'mixed'         => 'Mixto',
            'complete'      => 'Completo',
            default         => ucfirst(str_replace('_', ' ', $this->payment_type ?? '')),
        };
    }
}
