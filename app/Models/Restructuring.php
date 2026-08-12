<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Restructuring extends Model
{
    use HasFactory, BelongsToCompany;

    protected $table = 'restructurings';

    protected $fillable = [
        'company_id',
        'original_loan_id',
        'new_loan_id',
        'recorded_by',
        'type',
        'original_penalty',
        'forgiven_penalty',
        'remaining_penalty',
        'previous_periods',
        'new_periods',
        'balance_at_restructuring',
        'reason',
        'notes',
    ];

    protected $casts = [
        'original_penalty'          => 'decimal:2',
        'forgiven_penalty'          => 'decimal:2',
        'remaining_penalty'         => 'decimal:2',
        'balance_at_restructuring'  => 'decimal:2',
    ];

    public function originalLoan()
    {
        return $this->belongsTo(Loan::class, 'original_loan_id');
    }

    public function newLoan()
    {
        return $this->belongsTo(Loan::class, 'new_loan_id')->withTrashed();
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'forgiveness' => 'Condonación de mora',
            'extension'   => 'Extensión de plazo',
            'new_loan'    => 'Nuevo préstamo',
        };
    }
}
