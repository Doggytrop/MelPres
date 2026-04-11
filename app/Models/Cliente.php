<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'apellido',
        'telefono',
        'dui',
        'direccion',
        'referencias',
        'estado',
        'notas',
    ];

    // — Relaciones —
    public function prestamos()
    {
        return $this->hasMany(Prestamo::class);
    }

    public function prestamosActivos()
    {
        return $this->hasMany(Prestamo::class)->where('estado', 'activo');
    }

    // — Accessors —
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }
}