<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use App\Services\CompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
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
        'latitude',
        'longitude',
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
                    ->where('customer_documents.type', 'profile_photo')
                    ->whereExists(function ($query) {
                        $query->selectRaw('1')
                            ->from('customers as profile_photo_customers')
                            ->whereColumn(
                                'profile_photo_customers.id',
                                'customer_documents.customer_id'
                            )
                            ->whereColumn(
                                'profile_photo_customers.company_id',
                                'customer_documents.company_id'
                            );
                    })
                    ->latest('customer_documents.created_at');
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
        $companyId = app(CompanyContext::class)->getCompanyId();

        if (! $companyId || (int) $this->company_id !== (int) $companyId) {
            return null;
        }

        $photo = $this->relationLoaded('profilePhoto')
            ? $this->getRelation('profilePhoto')
            : $this->profilePhoto()
                ->where('customer_documents.company_id', $companyId)
                ->first();

        if (! $photo
            || (int) $photo->customer_id !== (int) $this->getKey()
            || (int) $photo->company_id !== (int) $this->company_id
            || (int) $photo->company_id !== (int) $companyId) {
            return null;
        }

        return $photo->view_url;
    }
    public function user()
    {
        return $this->hasOne(\App\Models\User::class, 'customer_id');
    }
}
