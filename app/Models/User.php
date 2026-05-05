<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'customer_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // — Relationships —
    public function customers()
    {
        return $this->hasMany(Customer::class, 'advisor_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'recorded_by');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // — Role helpers —
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['superadmin', 'admin']);
    }

    public function isAdvisor(): bool
    {
        return $this->role === 'advisor';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['superadmin', 'admin', 'advisor']);
    }
}