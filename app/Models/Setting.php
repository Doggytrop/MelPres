<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use App\Services\CompanyContext;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class Setting extends Model
{
    use BelongsToCompany;

    protected $table = 'settings';

    protected $fillable = [
        'company_id',
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    public static function get(string $key, $default = null)
    {
        $companyId = app(CompanyContext::class)->getCompanyId();

        if (! $companyId) {
            return $default;
        }

        $setting = static::where('company_id', $companyId)
            ->where('key', $key)
            ->first();

        if (! $setting) return $default;

        return match($setting->type) {
            'boolean' => (bool) $setting->value,
            'integer' => (int)  $setting->value,
            default   => $setting->value,
        };
    }

    public static function set(string $key, $value): void
    {
        $companyId = app(CompanyContext::class)->getCompanyId();

        if (! $companyId) {
            throw new LogicException('No se puede guardar una configuración sin una empresa activa.');
        }

        $setting = static::firstOrNew([
            'company_id' => $companyId,
            'key' => $key,
        ]);

        $setting->value = $value;

        if (! $setting->exists) {
            $setting->type = 'string';
            $setting->group = 'general';
        }

        $setting->save();
    }
}
