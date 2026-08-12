<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    private string $activityLogsTable = 'activity_logs';

    private string $usersTable = 'users';

    private string $insertTrigger = 'activity_logs_tenant_guard_bi';

    private string $updateTrigger = 'activity_logs_tenant_guard_bu';

    private string $userForeignKey = 'activity_logs_user_id_foreign';

    public function up(): void
    {
        $this->assertMySql();
        $this->assertRequiredStructure();
        $this->assertUserForeignKey();
        $this->assertExistingDataIsConsistent();

        $expected = $this->expectedTriggers();
        $existing = $this->triggersByName(array_keys($expected));

        foreach ($existing as $name => $trigger) {
            $this->assertTriggerMatches($trigger, $expected[$name]);
        }

        $this->assertOtherTriggersCanCoexist();

        foreach ($expected as $name => $definition) {
            if (isset($existing[$name])) {
                continue;
            }

            DB::unprepared($definition['create_sql']);
        }

        $created = $this->triggersByName(array_keys($expected));

        foreach ($expected as $name => $definition) {
            if (! isset($created[$name])) {
                throw new \RuntimeException("No se creó el trigger {$name}.");
            }

            $this->assertTriggerMatches($created[$name], $definition);
        }
    }

    public function down(): void
    {
        $this->assertMySql();

        $expected = $this->expectedTriggers();
        $existing = $this->triggersByName(array_keys($expected));

        foreach ($existing as $name => $trigger) {
            $this->assertTriggerMatches($trigger, $expected[$name]);
        }

        foreach (array_keys($expected) as $name) {
            if (! isset($existing[$name])) {
                continue;
            }

            DB::unprepared("DROP TRIGGER IF EXISTS `{$name}`");
        }
    }

    private function assertMySql(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new \RuntimeException(
                'Los triggers de aislamiento de activity_logs requieren MySQL.'
            );
        }
    }

    private function assertRequiredStructure(): void
    {
        foreach ([$this->activityLogsTable, $this->usersTable] as $table) {
            if (! Schema::hasTable($table)) {
                throw new \RuntimeException("No existe la tabla requerida {$table}.");
            }
        }

        foreach ([
            $this->activityLogsTable => ['company_id', 'user_id'],
            $this->usersTable => ['company_id', 'id'],
        ] as $table => $columns) {
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    throw new \RuntimeException("Falta la columna requerida {$table}.{$column}.");
                }
            }
        }
    }

    private function assertUserForeignKey(): void
    {
        $foreignKey = collect(Schema::getForeignKeys($this->activityLogsTable))
            ->first(fn (array $foreignKey): bool => strcasecmp(
                $foreignKey['name'],
                $this->userForeignKey
            ) === 0);

        if (! $foreignKey
            || $this->lowercaseColumns($foreignKey['columns']) !== ['user_id']
            || strcasecmp($foreignKey['foreign_table'], $this->usersTable) !== 0
            || $this->lowercaseColumns($foreignKey['foreign_columns']) !== ['id']
            || strtoupper($foreignKey['on_delete']) !== 'SET NULL'
            || ! in_array(strtoupper($foreignKey['on_update']), ['NO ACTION', 'RESTRICT'], true)) {
            throw new \RuntimeException(
                "{$this->userForeignKey} no conserva la definición NO ACTION / SET NULL esperada."
            );
        }
    }

    private function assertExistingDataIsConsistent(): void
    {
        $nullCompanyIds = DB::table($this->activityLogsTable)
            ->whereNotNull('user_id')
            ->whereNull('company_id')
            ->orderBy('id')
            ->pluck('id');

        if ($nullCompanyIds->isNotEmpty()) {
            throw new \RuntimeException(
                'Existen activity_logs con user_id y company_id NULL: '
                .$nullCompanyIds->implode(', ').'.'
            );
        }

        $invalidIds = DB::table("{$this->activityLogsTable} as activity_log")
            ->leftJoin("{$this->usersTable} as actor", function ($join): void {
                $join->on('actor.id', '=', 'activity_log.user_id')
                    ->on('actor.company_id', '=', 'activity_log.company_id');
            })
            ->whereNotNull('activity_log.user_id')
            ->whereNull('actor.id')
            ->orderBy('activity_log.id')
            ->pluck('activity_log.id');

        if ($invalidIds->isNotEmpty()) {
            throw new \RuntimeException(
                'Existen activity_logs cuyo user_id no pertenece a company_id: '
                .$invalidIds->implode(', ').'.'
            );
        }
    }

    private function expectedTriggers(): array
    {
        return [
            $this->insertTrigger => $this->triggerDefinition(
                $this->insertTrigger,
                'INSERT'
            ),
            $this->updateTrigger => $this->triggerDefinition(
                $this->updateTrigger,
                'UPDATE'
            ),
        ];
    }

    private function triggerDefinition(string $name, string $event): array
    {
        $statement = <<<SQL
BEGIN
    IF NEW.`user_id` IS NOT NULL THEN
        IF NEW.`company_id` IS NULL THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'activity_logs.company_id is required when user_id is present';
        END IF;

        IF NOT EXISTS (
            SELECT 1
            FROM `{$this->usersTable}`
            WHERE `id` = NEW.`user_id`
              AND `company_id` = NEW.`company_id`
        ) THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'activity_logs.user_id must belong to activity_logs.company_id';
        END IF;
    END IF;
END
SQL;

        return [
            'name' => $name,
            'event' => $event,
            'timing' => 'BEFORE',
            'table' => $this->activityLogsTable,
            'statement' => $statement,
            'create_sql' => "CREATE TRIGGER `{$name}`\n"
                ."BEFORE {$event} ON `{$this->activityLogsTable}`\n"
                ."FOR EACH ROW\n{$statement}",
        ];
    }

    private function triggersByName(array $names): array
    {
        return DB::table('information_schema.TRIGGERS')
            ->where('TRIGGER_SCHEMA', DB::raw('DATABASE()'))
            ->whereIn('TRIGGER_NAME', $names)
            ->get([
                'TRIGGER_NAME',
                'EVENT_MANIPULATION',
                'ACTION_TIMING',
                'EVENT_OBJECT_TABLE',
                'ACTION_STATEMENT',
            ])
            ->mapWithKeys(fn (object $trigger): array => [
                strtolower($trigger->TRIGGER_NAME) => $trigger,
            ])
            ->all();
    }

    private function otherBeforeTriggers(): array
    {
        return DB::table('information_schema.TRIGGERS')
            ->where('TRIGGER_SCHEMA', DB::raw('DATABASE()'))
            ->where('EVENT_OBJECT_TABLE', $this->activityLogsTable)
            ->where('ACTION_TIMING', 'BEFORE')
            ->whereIn('EVENT_MANIPULATION', ['INSERT', 'UPDATE'])
            ->whereNotIn('TRIGGER_NAME', [$this->insertTrigger, $this->updateTrigger])
            ->pluck('TRIGGER_NAME')
            ->all();
    }

    private function assertOtherTriggersCanCoexist(): void
    {
        $otherTriggers = $this->otherBeforeTriggers();

        if ($otherTriggers === []) {
            return;
        }

        $version = (string) DB::selectOne('SELECT VERSION() AS version')->version;

        if (stripos($version, 'mariadb') !== false
            || ! preg_match('/^\d+\.\d+\.\d+/', $version, $matches)
            || version_compare($matches[0], '5.7.2', '<')) {
            throw new \RuntimeException(
                'No se puede confirmar la coexistencia con los triggers existentes: '
                .implode(', ', $otherTriggers).". Versión detectada: {$version}."
            );
        }
    }

    private function assertTriggerMatches(object $trigger, array $expected): void
    {
        if (strcasecmp($trigger->TRIGGER_NAME, $expected['name']) !== 0
            || strtoupper($trigger->EVENT_MANIPULATION) !== $expected['event']
            || strtoupper($trigger->ACTION_TIMING) !== $expected['timing']
            || strcasecmp($trigger->EVENT_OBJECT_TABLE, $expected['table']) !== 0
            || $this->canonicalSql($trigger->ACTION_STATEMENT)
                !== $this->canonicalSql($expected['statement'])) {
            throw new \RuntimeException(
                "El trigger {$expected['name']} existe con una definición distinta."
            );
        }
    }

    private function canonicalSql(string $sql): string
    {
        $parts = preg_split(
            "/('(?:''|[^'])*')/",
            trim(str_replace('`', '', $sql)),
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        foreach ($parts as $index => $part) {
            if ($index % 2 === 0) {
                $parts[$index] = strtolower(preg_replace('/\s+/', ' ', $part));
            }
        }

        return trim(implode('', $parts));
    }

    private function lowercaseColumns(array $columns): array
    {
        return array_map('strtolower', $columns);
    }
};
