<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    private const CUSTOMER_UNIQUE = 'users_customer_id_unique';

    private const TENANT_INDEX = 'users_company_customer_idx';

    private const CUSTOMER_FOREIGN = 'users_customer_id_foreign';

    private const TENANT_FOREIGN = 'users_company_customer_tenant_fk';

    private const INSERT_TRIGGER = 'users_customer_tenant_guard_bi';

    private const UPDATE_TRIGGER = 'users_customer_tenant_guard_bu';

    private const FAILED_CHECK = 'users_customer_requires_company_chk';

    public function up(): void
    {
        $this->assertMySql();
        $this->assertRequiredStructure();
        $this->assertDataIsConsistent();
        $this->assertParentUnique();
        $this->assertFailedCheckIsAbsent();
        $this->assertProposedObjectsAreValid();

        $snapshot = $this->snapshot();

        if ($this->findIndex(self::CUSTOMER_UNIQUE) === null) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique('customer_id', self::CUSTOMER_UNIQUE);
            });
        }

        if ($this->findIndex(self::TENANT_INDEX) === null) {
            Schema::table('users', function (Blueprint $table): void {
                $table->index(['company_id', 'customer_id'], self::TENANT_INDEX);
            });
        }

        if ($this->findForeignKey(self::CUSTOMER_FOREIGN) === null) {
            Schema::table('users', function (Blueprint $table): void {
                $table->foreign('customer_id', self::CUSTOMER_FOREIGN)
                    ->references('id')
                    ->on('customers')
                    ->restrictOnUpdate()
                    ->restrictOnDelete();
            });
        }

        if ($this->findForeignKey(self::TENANT_FOREIGN) === null) {
            Schema::table('users', function (Blueprint $table): void {
                $table->foreign(
                    ['company_id', 'customer_id'],
                    self::TENANT_FOREIGN
                )
                    ->references(['company_id', 'id'])
                    ->on('customers')
                    ->restrictOnUpdate()
                    ->restrictOnDelete();
            });
        }

        foreach ($this->expectedTriggers() as $name => $definition) {
            if ($this->findTrigger($name) === null) {
                DB::unprepared($definition['create_sql']);
            }
        }

        $this->assertProposedObjectsAreValid(true);
        $this->assertDataIsConsistent();
        $this->assertSnapshotUnchanged($snapshot);
    }

    public function down(): void
    {
        $this->assertMySql();
        $this->assertRequiredStructure();
        $this->assertFailedCheckIsAbsent();
        $this->assertProposedObjectsAreValid();

        $snapshot = $this->snapshot();

        foreach ([self::UPDATE_TRIGGER, self::INSERT_TRIGGER] as $name) {
            if ($this->findTrigger($name) !== null) {
                DB::unprepared("DROP TRIGGER `{$name}`");
            }
        }

        if ($this->findForeignKey(self::TENANT_FOREIGN) !== null) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropForeign(self::TENANT_FOREIGN);
            });
        }

        if ($this->findForeignKey(self::CUSTOMER_FOREIGN) !== null) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropForeign(self::CUSTOMER_FOREIGN);
            });
        }

        if ($this->findIndex(self::TENANT_INDEX) !== null) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropIndex(self::TENANT_INDEX);
            });
        }

        if ($this->findIndex(self::CUSTOMER_UNIQUE) !== null) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique(self::CUSTOMER_UNIQUE);
            });
        }

        $this->assertProposedObjectsAreAbsent();
        $this->assertSnapshotUnchanged($snapshot);
    }

    private function assertMySql(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException(
                'The users.customer_id hardening migration requires MySQL.'
            );
        }
    }

    private function assertRequiredStructure(): void
    {
        foreach ([
            'users' => ['id', 'company_id', 'customer_id'],
            'customers' => ['id', 'company_id', 'deleted_at'],
        ] as $table => $columns) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Missing required table {$table}.");
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    throw new RuntimeException("Missing required column {$table}.{$column}.");
                }
            }
        }

        foreach ([
            ['users', 'company_id', true, ''],
            ['users', 'customer_id', true, ''],
            ['customers', 'company_id', false, ''],
            ['customers', 'id', false, 'auto_increment'],
        ] as [$table, $column, $nullable, $extra]) {
            $definition = $this->columnDefinition($table, $column);

            if (! $this->isExpectedColumnDefinition($definition, $nullable, $extra)) {
                throw new RuntimeException(
                    "Unexpected definition for {$table}.{$column}."
                );
            }
        }
    }

    private function assertDataIsConsistent(): void
    {
        $orphans = DB::table('users as user')
            ->leftJoin('customers as customer', 'customer.id', '=', 'user.customer_id')
            ->whereNotNull('user.customer_id')
            ->whereNull('customer.id')
            ->orderBy('user.id')
            ->pluck('user.id');

        if ($orphans->isNotEmpty()) {
            throw new RuntimeException(
                'Orphan users.customer_id values found for user IDs: '.$orphans->implode(', ')
            );
        }

        $crossTenant = DB::table('users as user')
            ->join('customers as customer', 'customer.id', '=', 'user.customer_id')
            ->whereRaw('NOT (user.company_id <=> customer.company_id)')
            ->orderBy('user.id')
            ->pluck('user.id');

        if ($crossTenant->isNotEmpty()) {
            throw new RuntimeException(
                'Cross-company customer links found for user IDs: '.$crossTenant->implode(', ')
            );
        }

        $duplicates = DB::table('users')
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('customer_id')
            ->pluck('customer_id');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Duplicate users.customer_id values found: '.$duplicates->implode(', ')
            );
        }

        $globalLinks = DB::table('users')
            ->whereNull('company_id')
            ->whereNotNull('customer_id')
            ->orderBy('id')
            ->pluck('id');

        if ($globalLinks->isNotEmpty()) {
            throw new RuntimeException(
                'Global users linked to customers found for user IDs: '
                .$globalLinks->implode(', ')
            );
        }
    }

    private function assertParentUnique(): void
    {
        $index = $this->findIndex(
            'customers_company_id_id_unique',
            'customers'
        );

        if ($index === null
            || ! $this->indexMatches($index, ['company_id', 'id'], true)
            || ! $this->isIndexVisible(
                'customers_company_id_id_unique',
                'customers'
            )) {
            throw new RuntimeException(
                'Missing exact customers_company_id_id_unique(company_id, id).'
            );
        }
    }

    private function assertProposedObjectsAreValid(bool $requireAll = false): void
    {
        $this->assertConstraintNamesAreAvailable();

        $objects = [
            self::CUSTOMER_UNIQUE => $this->findIndex(self::CUSTOMER_UNIQUE),
            self::TENANT_INDEX => $this->findIndex(self::TENANT_INDEX),
            self::CUSTOMER_FOREIGN => $this->findForeignKey(self::CUSTOMER_FOREIGN),
            self::TENANT_FOREIGN => $this->findForeignKey(self::TENANT_FOREIGN),
            self::INSERT_TRIGGER => $this->findTrigger(self::INSERT_TRIGGER),
            self::UPDATE_TRIGGER => $this->findTrigger(self::UPDATE_TRIGGER),
        ];

        if ($requireAll) {
            foreach ($objects as $name => $object) {
                if ($object === null) {
                    throw new RuntimeException("Required object {$name} was not created.");
                }
            }
        }

        if ($objects[self::CUSTOMER_UNIQUE] !== null
            && ! $this->indexMatches(
                $objects[self::CUSTOMER_UNIQUE],
                ['customer_id'],
                true
            )
            || $objects[self::CUSTOMER_UNIQUE] !== null
                && ! $this->isIndexVisible(self::CUSTOMER_UNIQUE)) {
            throw new RuntimeException(
                self::CUSTOMER_UNIQUE.' exists with an unexpected definition.'
            );
        }

        if ($objects[self::TENANT_INDEX] !== null
            && ! $this->indexMatches(
                $objects[self::TENANT_INDEX],
                ['company_id', 'customer_id'],
                false
            )
            || $objects[self::TENANT_INDEX] !== null
                && ! $this->isIndexVisible(self::TENANT_INDEX)) {
            throw new RuntimeException(
                self::TENANT_INDEX.' exists with an unexpected definition.'
            );
        }

        if ($objects[self::CUSTOMER_FOREIGN] !== null
            && ! $this->foreignKeyMatches(
                $objects[self::CUSTOMER_FOREIGN],
                ['customer_id'],
                'customers',
                ['id']
            )) {
            throw new RuntimeException(
                self::CUSTOMER_FOREIGN.' exists with an unexpected definition.'
            );
        }

        if ($objects[self::TENANT_FOREIGN] !== null
            && ! $this->foreignKeyMatches(
                $objects[self::TENANT_FOREIGN],
                ['company_id', 'customer_id'],
                'customers',
                ['company_id', 'id']
            )) {
            throw new RuntimeException(
                self::TENANT_FOREIGN.' exists with an unexpected definition.'
            );
        }

        foreach ($this->expectedTriggers() as $name => $definition) {
            if ($objects[$name] !== null
                && ! $this->triggerMatches($objects[$name], $definition)) {
                throw new RuntimeException(
                    "{$name} exists with an unexpected definition."
                );
            }
        }
    }

    private function assertConstraintNamesAreAvailable(): void
    {
        $names = [
            self::CUSTOMER_UNIQUE,
            self::CUSTOMER_FOREIGN,
            self::TENANT_FOREIGN,
        ];

        $conflict = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->whereRaw('CONSTRAINT_SCHEMA = DATABASE()')
            ->whereIn(DB::raw('LOWER(CONSTRAINT_NAME)'), array_map('strtolower', $names))
            ->whereRaw('LOWER(TABLE_NAME) <> ?', ['users'])
            ->first(['CONSTRAINT_NAME', 'TABLE_NAME']);

        if ($conflict !== null) {
            throw new RuntimeException(
                "Constraint {$conflict->CONSTRAINT_NAME} is already used by "
                ."table {$conflict->TABLE_NAME}."
            );
        }
    }

    private function assertProposedObjectsAreAbsent(): void
    {
        foreach ([
            self::CUSTOMER_UNIQUE => $this->findIndex(self::CUSTOMER_UNIQUE),
            self::TENANT_INDEX => $this->findIndex(self::TENANT_INDEX),
            self::CUSTOMER_FOREIGN => $this->findForeignKey(self::CUSTOMER_FOREIGN),
            self::TENANT_FOREIGN => $this->findForeignKey(self::TENANT_FOREIGN),
            self::INSERT_TRIGGER => $this->findTrigger(self::INSERT_TRIGGER),
            self::UPDATE_TRIGGER => $this->findTrigger(self::UPDATE_TRIGGER),
        ] as $name => $object) {
            if ($object !== null) {
                throw new RuntimeException("Rollback did not remove {$name}.");
            }
        }
    }

    private function findIndex(string $name, string $table = 'users'): ?array
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (strcasecmp((string) ($index['name'] ?? ''), $name) === 0) {
                return $index;
            }
        }

        return null;
    }

    private function findForeignKey(string $name): ?array
    {
        foreach (Schema::getForeignKeys('users') as $foreignKey) {
            if (strcasecmp((string) ($foreignKey['name'] ?? ''), $name) === 0) {
                return $foreignKey;
            }
        }

        return null;
    }

    private function assertFailedCheckIsAbsent(): void
    {
        $check = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->whereRaw('CONSTRAINT_SCHEMA = DATABASE()')
            ->whereRaw('LOWER(CONSTRAINT_NAME) = ?', [
                strtolower(self::FAILED_CHECK),
            ])
            ->first(['CONSTRAINT_NAME', 'TABLE_NAME', 'CONSTRAINT_TYPE']);

        if ($check !== null) {
            throw new RuntimeException(
                "Unexpected object {$check->CONSTRAINT_NAME} exists on "
                ."{$check->TABLE_NAME} as {$check->CONSTRAINT_TYPE}."
            );
        }
    }

    private function expectedTriggers(): array
    {
        return [
            self::INSERT_TRIGGER => $this->triggerDefinition(
                self::INSERT_TRIGGER,
                'INSERT'
            ),
            self::UPDATE_TRIGGER => $this->triggerDefinition(
                self::UPDATE_TRIGGER,
                'UPDATE'
            ),
        ];
    }

    private function triggerDefinition(string $name, string $event): array
    {
        $statement = <<<'SQL'
BEGIN
    IF NEW.`customer_id` IS NOT NULL AND NEW.`company_id` IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'A user linked to a customer must belong to a company';
    END IF;
END
SQL;

        return [
            'name' => $name,
            'event' => $event,
            'timing' => 'BEFORE',
            'table' => 'users',
            'statement' => $statement,
            'create_sql' => "CREATE TRIGGER `{$name}`\n"
                ."BEFORE {$event} ON `users`\n"
                ."FOR EACH ROW\n{$statement}",
        ];
    }

    private function findTrigger(string $name): ?object
    {
        return DB::table('information_schema.TRIGGERS')
            ->whereRaw('TRIGGER_SCHEMA = DATABASE()')
            ->whereRaw('LOWER(TRIGGER_NAME) = ?', [strtolower($name)])
            ->first([
                'TRIGGER_NAME',
                'EVENT_MANIPULATION',
                'ACTION_TIMING',
                'EVENT_OBJECT_TABLE',
                'ACTION_STATEMENT',
            ]);
    }

    private function indexMatches(array $index, array $columns, bool $unique): bool
    {
        return (bool) ($index['unique'] ?? false) === $unique
            && $this->sameColumns($index['columns'] ?? [], $columns)
            && strtoupper((string) ($index['type'] ?? 'BTREE')) === 'BTREE';
    }

    private function isIndexVisible(string $name, string $table = 'users'): bool
    {
        $metadataColumn = $this->informationSchemaHasColumn('STATISTICS', 'IS_VISIBLE')
            ? 'IS_VISIBLE'
            : ($this->informationSchemaHasColumn('STATISTICS', 'IGNORED') ? 'IGNORED' : null);

        if ($metadataColumn === null) {
            return false;
        }

        $metadata = DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->whereRaw('LOWER(INDEX_NAME) = ?', [strtolower($name)])
            ->first([$metadataColumn]);

        return $this->isIndexActive($metadata, $metadataColumn);
    }

    private function isIndexActive(?object $metadata, string $metadataColumn): bool
    {
        if ($metadata === null || ! property_exists($metadata, $metadataColumn)) {
            return false;
        }

        $value = strtoupper(trim((string) $metadata->{$metadataColumn}));

        return match (strtoupper($metadataColumn)) {
            'IS_VISIBLE' => $value === 'YES',
            'IGNORED' => $value === 'NO',
            default => false,
        };
    }

    private function foreignKeyMatches(
        array $foreignKey,
        array $columns,
        string $foreignTable,
        array $foreignColumns
    ): bool {
        return $this->sameColumns($foreignKey['columns'] ?? [], $columns)
            && strcasecmp(
                (string) ($foreignKey['foreign_table'] ?? ''),
                $foreignTable
            ) === 0
            && $this->sameColumns(
                $foreignKey['foreign_columns'] ?? [],
                $foreignColumns
            )
            && in_array(
                strtoupper((string) ($foreignKey['on_update'] ?? '')),
                ['RESTRICT', 'NO ACTION'],
                true
            )
            && in_array(
                strtoupper((string) ($foreignKey['on_delete'] ?? '')),
                ['RESTRICT', 'NO ACTION'],
                true
            );
    }

    private function triggerMatches(object $trigger, array $expected): bool
    {
        return strcasecmp($trigger->TRIGGER_NAME, $expected['name']) === 0
            && strtoupper($trigger->EVENT_MANIPULATION) === $expected['event']
            && strtoupper($trigger->ACTION_TIMING) === $expected['timing']
            && strcasecmp($trigger->EVENT_OBJECT_TABLE, $expected['table']) === 0
            && $this->canonicalSql($trigger->ACTION_STATEMENT)
                === $this->canonicalSql($expected['statement']);
    }

    private function canonicalSql(string $sql): string
    {
        return preg_replace('/\s+/', '', strtolower(str_replace('`', '', trim($sql)))) ?? '';
    }

    private function snapshot(): array
    {
        return [
            'rows' => DB::table('users')->orderBy('id')->get()->map(
                fn (object $row): array => (array) $row
            )->all(),
            'columns' => Schema::getColumns('users'),
            'indexes' => array_values(array_filter(
                Schema::getIndexes('users'),
                fn (array $index): bool => ! in_array(
                    strtolower((string) ($index['name'] ?? '')),
                    [strtolower(self::CUSTOMER_UNIQUE), strtolower(self::TENANT_INDEX)],
                    true
                )
            )),
            'foreign_keys' => array_values(array_filter(
                Schema::getForeignKeys('users'),
                fn (array $foreignKey): bool => ! in_array(
                    strtolower((string) ($foreignKey['name'] ?? '')),
                    [strtolower(self::CUSTOMER_FOREIGN), strtolower(self::TENANT_FOREIGN)],
                    true
                )
            )),
            'checks' => $this->checkSnapshot(),
            'triggers' => $this->otherTriggerSnapshot(),
        ];
    }

    private function assertSnapshotUnchanged(array $snapshot): void
    {
        $current = $this->snapshot();

        foreach (array_keys($snapshot) as $key) {
            if ($snapshot[$key] !== $current[$key]) {
                throw new RuntimeException(
                    "Unexpected change detected in users {$key}."
                );
            }
        }
    }

    private function checkSnapshot(): array
    {
        $hasEnforced = $this->informationSchemaHasColumn(
            'TABLE_CONSTRAINTS',
            'ENFORCED'
        );
        $columns = [
            'tc.CONSTRAINT_NAME',
            'tc.CONSTRAINT_TYPE',
            'cc.CHECK_CLAUSE',
        ];

        if ($hasEnforced) {
            $columns[] = 'tc.ENFORCED';
        }

        return DB::table('information_schema.TABLE_CONSTRAINTS as tc')
            ->join('information_schema.CHECK_CONSTRAINTS as cc', function ($join): void {
                $join->on('cc.CONSTRAINT_SCHEMA', '=', 'tc.CONSTRAINT_SCHEMA')
                    ->on('cc.CONSTRAINT_NAME', '=', 'tc.CONSTRAINT_NAME');
            })
            ->whereRaw('tc.CONSTRAINT_SCHEMA = DATABASE()')
            ->where('tc.TABLE_NAME', 'users')
            ->where('tc.CONSTRAINT_TYPE', 'CHECK')
            ->orderBy('tc.CONSTRAINT_NAME')
            ->get($columns)
            ->map(function (object $check) use ($hasEnforced): array {
                $normalized = $this->normalizeCheckConstraint($check, $hasEnforced);

                if ($normalized === null) {
                    throw new RuntimeException(
                        'Unexpected CHECK constraint metadata for users.'
                    );
                }

                return $normalized;
            })
            ->all();
    }

    private function normalizeCheckConstraint(
        ?object $constraint,
        bool $hasEnforced
    ): ?array {
        if ($constraint === null
            || ! property_exists($constraint, 'CONSTRAINT_NAME')
            || ! property_exists($constraint, 'CONSTRAINT_TYPE')
            || ! property_exists($constraint, 'CHECK_CLAUSE')
            || strtoupper((string) $constraint->CONSTRAINT_TYPE) !== 'CHECK') {
            return null;
        }

        $enforced = null;

        if ($hasEnforced) {
            if (! property_exists($constraint, 'ENFORCED')) {
                return null;
            }

            $value = strtoupper(trim((string) $constraint->ENFORCED));

            if (! in_array($value, ['YES', 'NO'], true)) {
                return null;
            }

            $enforced = $value === 'YES';
        }

        return [
            'CONSTRAINT_NAME' => (string) $constraint->CONSTRAINT_NAME,
            'CONSTRAINT_TYPE' => 'CHECK',
            'CHECK_CLAUSE' => (string) $constraint->CHECK_CLAUSE,
            'ENFORCEMENT_METADATA_AVAILABLE' => $hasEnforced,
            'ENFORCED' => $enforced,
        ];
    }

    private function informationSchemaHasColumn(string $table, string $column): bool
    {
        return DB::table('information_schema.COLUMNS')
            ->whereRaw("TABLE_SCHEMA = 'information_schema'")
            ->whereRaw('UPPER(TABLE_NAME) = ?', [strtoupper($table)])
            ->whereRaw('UPPER(COLUMN_NAME) = ?', [strtoupper($column)])
            ->exists();
    }

    private function otherTriggerSnapshot(): array
    {
        return DB::table('information_schema.TRIGGERS')
            ->whereRaw('TRIGGER_SCHEMA = DATABASE()')
            ->where('EVENT_OBJECT_TABLE', 'users')
            ->whereNotIn('TRIGGER_NAME', [
                self::INSERT_TRIGGER,
                self::UPDATE_TRIGGER,
            ])
            ->orderBy('TRIGGER_NAME')
            ->get([
                'TRIGGER_NAME',
                'EVENT_MANIPULATION',
                'ACTION_TIMING',
                'EVENT_OBJECT_TABLE',
                'ACTION_STATEMENT',
            ])
            ->map(fn (object $trigger): array => (array) $trigger)
            ->all();
    }

    private function columnDefinition(string $table, string $column): object
    {
        $definition = DB::table('information_schema.COLUMNS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->first([
                'DATA_TYPE',
                'COLUMN_TYPE',
                'IS_NULLABLE',
                'COLUMN_DEFAULT',
                'EXTRA',
            ]);

        if ($definition === null) {
            throw new RuntimeException("Missing required column {$table}.{$column}.");
        }

        return $definition;
    }

    private function isBigIntUnsigned(object $column): bool
    {
        return strcasecmp((string) ($column->DATA_TYPE ?? ''), 'bigint') === 0
            && preg_match('/\bunsigned\b/i', (string) ($column->COLUMN_TYPE ?? '')) === 1;
    }

    private function isExpectedColumnDefinition(
        object $column,
        bool $nullable,
        string $extra
    ): bool {
        return $this->isBigIntUnsigned($column)
            && (($column->IS_NULLABLE ?? '') === 'YES') === $nullable
            && property_exists($column, 'COLUMN_DEFAULT')
            && $this->isSqlNullDefault($column->COLUMN_DEFAULT)
            && property_exists($column, 'EXTRA')
            && strcasecmp(trim((string) $column->EXTRA), $extra) === 0;
    }

    private function isSqlNullDefault(mixed $default): bool
    {
        return $default === null
            || (is_string($default) && strcasecmp(trim($default), 'NULL') === 0);
    }

    private function sameColumns(array $actual, array $expected): bool
    {
        return array_map('strtolower', $actual) === array_map('strtolower', $expected);
    }
};
