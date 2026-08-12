<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    private const LEGACY_IDS = [
        1, 7, 10, 12, 13, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25,
    ];

    private string $activityLogsTable = 'activity_logs';

    private string $usersTable = 'users';

    public function up(): void
    {
        $this->assertRequiredStructure();

        DB::transaction(function (): void {
            $logs = $this->legacyLogsForUpdate();

            if ($logs->isEmpty()) {
                $this->assertNoUnexpectedNullLogs();

                return;
            }

            $users = $this->usersForLogs($logs);

            $this->assertNoUnexpectedNullLogs();
            $this->validateLogs($logs, $users, allowNullCompany: true);

            $before = $this->snapshotWithoutCompanyId($logs);

            foreach ($logs as $log) {
                if ($log->company_id !== null) {
                    continue;
                }

                $companyId = $users->get($log->user_id)->company_id;
                $updated = DB::table($this->activityLogsTable)
                    ->where('id', $log->id)
                    ->whereNull('company_id')
                    ->update(['company_id' => $companyId]);

                if ($updated !== 1) {
                    throw new \RuntimeException(
                        "No se pudo actualizar de forma segura activity_logs.id {$log->id}."
                    );
                }
            }

            $updatedLogs = $this->legacyLogsForUpdate();
            $updatedUsers = $this->usersForLogs($updatedLogs);

            $this->validateLogs($updatedLogs, $updatedUsers, allowNullCompany: false);
            $this->assertNoUnexpectedNullLogs();
            $this->assertExpectedDistribution($updatedLogs);
            $this->assertUnchangedExceptCompanyId(
                $before,
                $this->snapshotWithoutCompanyId($updatedLogs)
            );
        });
    }

    public function down(): void
    {
        $this->assertRequiredStructure();

        // This deliberately reverses a historical correction and is intended
        // only for an immediate production rollback.
        DB::transaction(function (): void {
            $logs = $this->legacyLogsForUpdate();

            if ($logs->isEmpty()) {
                return;
            }

            $users = $this->usersForLogs($logs);

            $this->validateLogs($logs, $users, allowNullCompany: false);
            $this->assertExpectedDistribution($logs);

            $before = $this->snapshotWithoutCompanyId($logs);

            foreach ($logs as $log) {
                $updated = DB::table($this->activityLogsTable)
                    ->where('id', $log->id)
                    ->where('company_id', $log->company_id)
                    ->update(['company_id' => null]);

                if ($updated !== 1) {
                    throw new \RuntimeException(
                        "No se pudo revertir de forma segura activity_logs.id {$log->id}."
                    );
                }
            }

            $revertedLogs = $this->legacyLogsForUpdate();

            if ($revertedLogs->contains(fn (object $log): bool => $log->company_id !== null)) {
                throw new \RuntimeException(
                    'El rollback no dejó todos los activity_logs legacy con company_id NULL.'
                );
            }

            $this->assertUnchangedExceptCompanyId(
                $before,
                $this->snapshotWithoutCompanyId($revertedLogs)
            );
        });
    }

    private function assertRequiredStructure(): void
    {
        foreach ([$this->activityLogsTable, $this->usersTable] as $table) {
            if (! Schema::hasTable($table)) {
                throw new \RuntimeException("No existe la tabla requerida {$table}.");
            }
        }

        $requiredColumns = [
            $this->activityLogsTable => [
                'id',
                'company_id',
                'user_id',
                'user_name',
                'module',
                'action',
                'description',
                'model_type',
                'model_id',
            ],
            $this->usersTable => ['id', 'company_id', 'name'],
        ];

        foreach ($requiredColumns as $table => $columns) {
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    throw new \RuntimeException("Falta la columna requerida {$table}.{$column}.");
                }
            }
        }
    }

    private function legacyLogsForUpdate(): Collection
    {
        $logs = DB::table($this->activityLogsTable)
            ->whereIn('id', self::LEGACY_IDS)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $foundIds = $logs->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $missingIds = array_values(array_diff(self::LEGACY_IDS, $foundIds));

        if ($logs->isNotEmpty() && $missingIds !== []) {
            throw new \RuntimeException(
                'Faltan activity_logs legacy esperados: '.implode(', ', $missingIds).'.'
            );
        }

        return $logs;
    }

    private function usersForLogs(Collection $logs): Collection
    {
        return DB::table($this->usersTable)
            ->whereIn('id', $logs->pluck('user_id')->filter()->unique()->values())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    private function assertNoUnexpectedNullLogs(): void
    {
        $unexpectedIds = DB::table($this->activityLogsTable)
            ->whereNull('company_id')
            ->whereNotIn('id', self::LEGACY_IDS)
            ->orderBy('id')
            ->lockForUpdate()
            ->pluck('id');

        if ($unexpectedIds->isNotEmpty()) {
            throw new \RuntimeException(
                'Existen activity_logs con company_id NULL no contemplados: '
                .$unexpectedIds->implode(', ').'.'
            );
        }
    }

    private function validateLogs(
        Collection $logs,
        Collection $users,
        bool $allowNullCompany
    ): void {
        foreach ($logs as $log) {
            if ($log->module !== 'auth'
                || $log->action !== 'login'
                || $log->description !== 'Inició sesión'
                || $log->model_type !== null
                || $log->model_id !== null
                || $log->user_id === null) {
                throw new \RuntimeException(
                    "activity_logs.id {$log->id} no conserva la estructura legacy esperada."
                );
            }

            $user = $users->get($log->user_id);

            if (! $user) {
                throw new \RuntimeException(
                    "No existe el usuario {$log->user_id} de activity_logs.id {$log->id}."
                );
            }

            if ($user->company_id === null) {
                throw new \RuntimeException(
                    "El usuario {$user->id} no tiene company_id."
                );
            }

            if ($log->user_name !== $user->name) {
                throw new \RuntimeException(
                    "user_name no coincide para activity_logs.id {$log->id}."
                );
            }

            if ($log->company_id === null && $allowNullCompany) {
                continue;
            }

            if ($log->company_id === null
                || (int) $log->company_id !== (int) $user->company_id) {
                throw new \RuntimeException(
                    "company_id no coincide con el usuario en activity_logs.id {$log->id}."
                );
            }
        }
    }

    private function assertExpectedDistribution(Collection $logs): void
    {
        $distribution = $logs
            ->countBy(fn (object $log): int => (int) $log->company_id);

        if ($distribution->count() !== 2
            || $distribution->get(1, 0) !== 9
            || $distribution->get(2, 0) !== 6) {
            throw new \RuntimeException(
                'La distribución final no coincide con 9 logs para company_id 1 '
                .'y 6 logs para company_id 2.'
            );
        }
    }

    private function snapshotWithoutCompanyId(Collection $logs): array
    {
        return $logs->mapWithKeys(function (object $log): array {
            $attributes = (array) $log;
            unset($attributes['company_id']);
            ksort($attributes);

            return [(int) $log->id => $attributes];
        })->all();
    }

    private function assertUnchangedExceptCompanyId(array $before, array $after): void
    {
        if ($before !== $after) {
            throw new \RuntimeException(
                'El backfill modificó campos distintos de activity_logs.company_id.'
            );
        }
    }
};
