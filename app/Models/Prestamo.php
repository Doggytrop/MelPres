<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Prestamo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cliente_id',
        'tipo',
        'frecuencia_pago',
        'monto_original',
        'saldo_restante',
        'interes_rate',
        'interes_acumulado',
        'mora_tipo',
        'mora_valor',
        'dias_gracia',
        'mora_acumulada',
        'fecha_inicio',
        'fecha_vencimiento',
        'fecha_proximo_pago',
        'estado',
        'observaciones',
        'interes_pendiente',
    ];

    protected $casts = [
        'fecha_inicio'        => 'date',
        'fecha_vencimiento'   => 'date',
        'fecha_proximo_pago'  => 'date',
        'monto_original'      => 'decimal:2',
        'saldo_restante'      => 'decimal:2',
        'interes_rate'        => 'decimal:2',
        'interes_acumulado'   => 'decimal:2',
        'mora_acumulada'      => 'decimal:2',
    ];

    // — Relaciones —
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class)->latest();
    }

    // — Accessors útiles —

    // Interés mensual en dinero
    public function getInteresMensualAttribute(): float
    {
        return round($this->monto_original * ($this->interes_rate / 100), 2);
    }

    // Total a pagar en préstamo tipo plazo
    public function getTotalPlazoAttribute(): float
    {
        $interes_total = $this->interes_rate * $this->numero_periodos ?? 1;
        return round($this->monto_original + ($this->monto_original * $interes_total / 100), 2);
    }

    // Pago sugerido según frecuencia (solo orientativo)
    public function getPagoSugeridoAttribute(): float
    {
        if (!$this->numero_periodos || $this->numero_periodos == 0) return 0;
        return round($this->saldo_restante / $this->numero_periodos, 2);
    }

    public function estaVencido(): bool
    {
        if (!$this->fecha_proximo_pago) return false;
        return now()->gt($this->fecha_proximo_pago->addDays($this->dias_gracia ?? 0));
    }
}