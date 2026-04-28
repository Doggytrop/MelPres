<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'rol'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // — Relaciones —
    public function customers()
    {
        return $this->hasMany(customer::class, 'advisor_id');
    }

    public function payments()
    {
        return $this->hasMany(payment::class, 'recorded_by');
    }

    // — Helpers —
    public function isAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    public function esadvisor(): bool
    {
        return $this->rol === 'advisor';
    }
}