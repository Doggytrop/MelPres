<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerDocument extends Model
{
    use HasFactory;

    protected $table = 'customer_documents';

    protected $fillable = [
        'customer_id',
        'type',
        'original_name',
        'path',
        'mime_type',
        'size',
        'notes',
    ];

    // — Relationships —
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // — Helpers —
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'profile_photo'  => 'Foto de perfil',
            'id_front'       => 'INE (frente)',
            'id_back'        => 'INE (reverso)',
            'address_proof'  => 'Comprobante de domicilio',
            'payroll'        => 'Nómina',
            'other'          => 'Otro',
            default          => $this->type ?? 'Documento',
        };
    }

    public function isImage(): bool
    {
        return str_contains($this->mime_type, 'image');
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size ?? 0;
        if ($bytes < 1024)    return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}