<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use App\Services\CompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use LogicException;

class ActivityLog extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
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
        $contextCompanyId = app(CompanyContext::class)->getCompanyId();
        $modelCompanyId = $model instanceof Model ? $model->getAttribute('company_id') : null;
        $user = auth()->user();
        $userCompanyId = $user?->company_id;

        $companyIds = array_values(array_unique(array_map(
            'intval',
            array_filter([$contextCompanyId, $modelCompanyId, $userCompanyId], fn ($id) => $id !== null)
        )));

        $companyId = $contextCompanyId ?? $modelCompanyId ?? $userCompanyId;
        $userIsConsistent = ! $userCompanyId || ! $companyId || (int) $userCompanyId === (int) $companyId;

        if (count($companyIds) > 1) {
            $companyId = $modelCompanyId ?? $contextCompanyId ?? $userCompanyId;
            $userIsConsistent = ! $userCompanyId || (int) $userCompanyId === (int) $companyId;

            Log::warning('Se detectó una atribución empresarial inconsistente al crear ActivityLog.', [
                'context_company_id' => $contextCompanyId,
                'model_company_id' => $modelCompanyId,
                'user_company_id' => $userCompanyId,
                'selected_company_id' => $companyId,
                'module' => $module,
                'action' => $action,
                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model?->id,
            ]);
        }

        if (! $companyId && $module !== 'auth') {
            throw new LogicException('No se puede crear un log empresarial sin una empresa atribuible.');
        }

        return self::create([
            'company_id'  => $companyId,
            'user_id'     => $userIsConsistent ? $user?->id : null,
            'user_name'   => $userIsConsistent ? ($user?->name ?? 'Sistema') : 'Sistema',
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
