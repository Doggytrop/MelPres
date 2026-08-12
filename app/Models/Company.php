<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'email',
        'phone',
        'address',
        'logo',
        'timezone',
        'currency_code',
        'currency_symbol',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function customerDocuments()
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    public function restructurings()
    {
        return $this->hasMany(Restructuring::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(CompanySubscription::class);
    }

    public function primaryAdmin(): HasOne
    {
        return $this->hasOne(User::class)
            ->where('role', 'admin')
            ->oldestOfMany();
    }
}
