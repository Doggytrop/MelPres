<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClienteDocumento extends Model
{
    use HasFactory;

    protected $table = 'cliente_documentos';

    protected $fillable = [
        'cliente_id',
        'tipo',
        'nombre_original',
        'ruta',
        'mime_type',
        'tamanio',
        'notas',
    ];

    // — Relaciones —
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    // — Helpers —
    public function getTipoLabelAttribute(): string
    {
        return match($this->tipo) {
            'foto_perfil'           => 'Foto de perfil',
            'ine_frente'            => 'INE (frente)',
            'ine_reverso'           => 'INE (reverso)',
            'comprobante_domicilio' => 'Comprobante de domicilio',
            'nomina'                => 'Nómina',
            'otro'                  => 'Otro',
        };
    }

    public function esImagen(): bool
    {
        return str_contains($this->mime_type, 'image');
    }

    public function getTamanioFormateadoAttribute(): string
    {
        $bytes = $this->tamanio ?? 0;
        if ($bytes < 1024)        return $bytes . ' B';
        if ($bytes < 1048576)     return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}