<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reestructuracion extends Model
{
    use HasFactory;

    protected $table = 'reestructuraciones';

    protected $fillable = [
        'prestamo_original_id',
        'prestamo_nuevo_id',
        'registrado_por',
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

    public function prestamoOriginal()
    {
        return $this->belongsTo(Prestamo::class, 'prestamo_original_id');
    }

    public function prestamoNuevo()
    {
        return $this->belongsTo(Prestamo::class, 'prestamo_nuevo_id');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function getTipoLabelAttribute(): string
    {
        return match($this->tipo) {
            'condonacion'    => 'Condonación de mora',
            'extension'      => 'Extensión de plazo',
            'nuevo_prestamo' => 'Nuevo préstamo',
        };
    }
}