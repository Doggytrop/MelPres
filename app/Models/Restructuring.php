<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Restructuring extends Model
{
    use HasFactory;

    protected $table = 'restructurings';

    protected $fillable = [
        'loan_original_id',
        'loan_nuevo_id',
        'recorded_by',
        'tipo',
        'mora_original',
        'mora_condonada',
        'mora_restante',
        'periodos_anteriores',
        'periodos_nuevos',
        'saldo_al_reestructurar',
        'motivo',
        'observaciones',
    ];

    protected $casts = [
        'mora_original'          => 'decimal:2',
        'mora_condonada'         => 'decimal:2',
        'mora_restante'          => 'decimal:2',
        'saldo_al_reestructurar' => 'decimal:2',
    ];

    public function loanOriginal()
    {
        return $this->belongsTo(loan::class, 'loan_original_id');
    }

    public function loanNuevo()
    {
        return $this->belongsTo(loan::class, 'loan_nuevo_id');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'forgiveness'    => 'Condonación de mora',
            'extension'      => 'Extensión de term',
            'new_loan' => 'Nuevo préstamo',
        };
    }
}