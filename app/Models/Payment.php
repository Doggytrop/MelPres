<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'amount_paid',
        'penalty_payment',
        'interest_payment',
        'capital_payment',
        'payment_date',
        'expected_date',
        'payment_type',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'payment_date'     => 'date',
        'expected_date' => 'date',
        'amount_paid'   => 'decimal:2',
        'penalty_payment'     => 'decimal:2',
        'interest_payment'  => 'decimal:2',
        'capital_payment'  => 'decimal:2',
    ];

    // — Relaciones —
    public function loan()
    {
        return $this->belongsTo(loan::class);
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}