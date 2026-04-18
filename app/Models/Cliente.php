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
        'documento_tipo',
        'documento_numero',
        'direccion',
        'referencias',
        'estado',
        'notas',
        'asesor_id',
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
    // — Relaciones —
    public function documentos()
    {
        return $this->hasMany(ClienteDocumento::class);
    }

    public function fotoPerfil()
    {
        return $this->hasOne(ClienteDocumento::class)
                    ->where('tipo', 'foto_perfil')
                    ->latest();
    }

    // — Accessor foto —
    public function getFotoUrlAttribute(): ?string
    {
        $foto = $this->fotoPerfil;
        return $foto ? asset('storage/' . $foto->ruta) : null;
    }
        public function asesor()
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }
    protected $casts = [
    'score_actualizado_at' => 'datetime',
    ];
    }