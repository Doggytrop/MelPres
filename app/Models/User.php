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
    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'asesor_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'registrado_por');
    }

    // — Helpers —
    public function esAdmin(): bool
    {
        return $this->rol === 'administrador';
    }

    public function esAsesor(): bool
    {
        return $this->rol === 'asesor';
    }
}