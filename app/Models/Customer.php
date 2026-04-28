<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'document_type',
        'document_number',
        'address',
        'references',
        'status',
        'notes',
        'score',
        'score_updated_at',
    ];

    protected $casts = [
        'score_updated_at' => 'datetime',
    ];

    // — Relationships —
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function activeLoans()
    {
        return $this->hasMany(Loan::class)->where('status', 'active');
    }

    public function documents()
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function profilePhoto()
    {
        return $this->hasOne(CustomerDocument::class)
                    ->where('type', 'profile_photo')
                    ->latest();
    }

    public function advisor()
    {
        return $this->belongsTo(User::class, 'advisor_id');
    }

    // — Accessors —
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getFirstNameCompleteAttribute(): string
    {
        return $this->full_name;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        $photo = $this->profilePhoto;
        return $photo ? asset('storage/' . $photo->path) : null;
    }
}