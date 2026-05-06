<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'module',
        'description',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getActionBadgeAttribute(): array
    {
        return match($this->action) {
            'create'  => ['bg' => '#e8f5e9', 'color' => '#1f6b21', 'label' => 'Creación'],
            'update'  => ['bg' => '#e3f2fd', 'color' => '#1565c0', 'label' => 'Edición'],
            'delete'  => ['bg' => '#fdecea', 'color' => '#c0392b', 'label' => 'Eliminación'],
            'payment' => ['bg' => '#e8f5e9', 'color' => '#1f6b21', 'label' => 'Pago'],
            'login'   => ['bg' => '#f3e5f5', 'color' => '#6a1b9a', 'label' => 'Login'],
            'restructure' => ['bg' => '#fff3e0', 'color' => '#e65100', 'label' => 'Reestructuración'],
            default   => ['bg' => '#f5f5f5', 'color' => '#888', 'label' => $this->action],
        };
    }

    public function getModuleBadgeAttribute(): array
    {
        return match($this->module) {
            'customers' => ['bg' => '#e3f2fd', 'color' => '#1565c0', 'label' => 'Clientes'],
            'loans'     => ['bg' => '#e8f5e9', 'color' => '#1f6b21', 'label' => 'Préstamos'],
            'payments'  => ['bg' => '#e8f5e9', 'color' => '#1f6b21', 'label' => 'Pagos'],
            'users'     => ['bg' => '#f3e5f5', 'color' => '#6a1b9a', 'label' => 'Usuarios'],
            'settings'  => ['bg' => '#f5f5f5', 'color' => '#555',    'label' => 'Configuración'],
            'restructuring' => ['bg' => '#fff3e0', 'color' => '#e65100', 'label' => 'Reestructuración'],
            'auth'      => ['bg' => '#f3e5f5', 'color' => '#6a1b9a', 'label' => 'Autenticación'],
            default     => ['bg' => '#f5f5f5', 'color' => '#888',    'label' => $this->module],
        };
    }

    public static function log(string $action, string $module, string $description, $model = null, ?array $oldValues = null, ?array $newValues = null): self
    {
        return self::create([
            'user_id'     => auth()->id(),
            'user_name'   => auth()->user()?->name ?? 'Sistema',
            'action'      => $action,
            'module'      => $module,
            'description' => $description,
            'model_type'  => $model ? get_class($model) : null,
            'model_id'    => $model?->id,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'ip_address'  => request()->ip(),
        ]);
    }
}