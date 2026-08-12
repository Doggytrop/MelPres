<?php

namespace App\Services;

use App\Models\SaasActivityLog;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class SaasAuditService
{
    private const SENSITIVE_KEY_FRAGMENTS = [
        'password',
        'token',
        'secret',
        'hash',
    ];

    public function record(
        string $action,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): SaasActivityLog {
        $actor = auth()->user();

        if (! $actor?->isSuperAdmin()) {
            throw new LogicException('La auditoría SaaS requiere un superadmin autenticado.');
        }

        $request = app()->bound('request') ? request() : null;

        return SaasActivityLog::create([
            'actor_user_id' => $actor->id,
            'actor_name' => $actor->name,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'old_values' => $this->sanitize($oldValues),
            'new_values' => $this->sanitize($newValues),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    private function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $sanitized = [];

        foreach ($values as $key => $value) {
            $normalizedKey = strtolower((string) $key);
            $isSensitive = collect(self::SENSITIVE_KEY_FRAGMENTS)
                ->contains(fn (string $fragment): bool => str_contains($normalizedKey, $fragment));

            if ($isSensitive) {
                continue;
            }

            $sanitized[$key] = is_array($value) ? $this->sanitize($value) : $value;
        }

        return $sanitized;
    }
}
