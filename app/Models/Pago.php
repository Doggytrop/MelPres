<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pago extends Model
{
    use HasFactory;

    protected $fillable = [
        'prestamo_id',
        'monto_pagado',
        'abono_mora',
        'abono_interes',
        'abono_capital',
        'fecha_pago',
        'fecha_esperada',
        'tipo_pago',
        'observaciones',
        'registrado_por',
    ];

    protected $casts = [
        'fecha_pago'     => 'date',
        'fecha_esperada' => 'date',
        'monto_pagado'   => 'decimal:2',
        'abono_mora'     => 'decimal:2',
        'abono_interes'  => 'decimal:2',
        'abono_capital'  => 'decimal:2',
    ];

    // — Relaciones —
    public function prestamo()
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}