<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    private string $restructuringsTable = 'restructurings';

    private string $companiesTable = 'companies';

    private string $loansTable = 'loans';

    private string $usersTable = 'users';

    private array $expectedOtherForeignKeys = [
        'restruct_new_loan_tenant_fk' => [
            'columns' => ['company_id', 'new_loan_id'],
            'table' => 'loans',
            'foreign_columns' => ['company_id', 'id'],
            'update' => 'RESTRICT',
            'delete' => 'RESTRICT',
        ],
        'restruct_original_loan_tenant_fk' => [
            'columns' => ['company_id', 'original_loan_id'],
            'table' => 'loans',
            'foreign_columns' => ['company_id', 'id'],
            'update' => 'RESTRICT',
            'delete' => 'RESTRICT',
        ],
        'restruct_recorded_by_tenant_fk' => [
            'columns' => ['company_id', 'recorded_by'],
            'table' => 'users',
            'foreign_columns' => ['company_id', 'id'],
            'update' => 'RESTRICT',
            'delete' => 'RESTRICT',
        ],
        'restructurings_new_loan_id_foreign' => [
            'columns' => ['new_loan_id'],
            'table' => 'loans',
            'foreign_columns' => ['id'],
            'update' => 'RESTRICT',
            'delete' => 'RESTRICT',
        ],
        'restructurings_original_loan_id_foreign' => [
            'columns' => ['original_loan_id'],
            'table' => 'loans',
            'foreign_columns' => ['id'],
            'update' => 'NO ACTION',
            'delete' => 'RESTRICT',
        ],
        'restructurings_recorded_by_foreign' => [
            'columns' => ['recorded_by'],
            'table' => 'users',
            'foreign_columns' => ['id'],
            'update' => 'NO ACTION',
            'delete' => 'RESTRICT',
        ],
    ];

    public function up(): void
    {
        $this->assertMySql();
        $this->assertRequiredStructure();
        $this->assertDataIsConsistent();

        $snapshot = $this->snapshot();
        $columnState = $this->columnState();
        $foreignKey = $this->companyForeignKey();
        $foreignKeyState = $this->foreignKeyState($foreignKey);
        $foreignKeyName = $foreignKey['name'] ?? $this->fallbackForeignKeyName();

        $this->assertConstraintNameCanBeUsed($foreignKeyName, $foreignKey);

        if ($columnState === 'final' && $foreignKeyState === 'final') {
            $this->assertFinalState($foreignKeyName, $snapshot);

            return;
        }

        if ($foreignKey !== null) {
            $this->dropForeignKey($foreignKeyName);
        }

        if ($columnState === 'initial') {
            try {
                DB::statement(sprintf(
                    'ALTER TABLE %s MODIFY COLUMN %s BIGINT UNSIGNED NOT NULL',
                    $this->quoteIdentifier($this->restructuringsTable),
                    $this->quoteIdentifier('company_id')
                ));
            } catch (Throwable $exception) {
                throw new RuntimeException(
                    'MySQL could not make restructurings.company_id NOT NULL '
                    .'while all verified loan/user foreign keys remained installed. '
                    .'No other foreign key or index was removed; the missing simple '
                    .'company foreign key is a recoverable partial state.',
                    0,
                    $exception
                );
            }
        }

        $this->addForeignKey($foreignKeyName, 'RESTRICT', 'RESTRICT');
        $this->assertFinalState($foreignKeyName, $snapshot);
    }

    public function down(): void
    {
        $this->assertMySql();
        $this->assertRequiredStructure();
        $this->assertReferencedCompaniesExist();

        $snapshot = $this->snapshot();
        $columnState = $this->columnState();
        $foreignKey = $this->companyForeignKey();
        $foreignKeyState = $this->foreignKeyState($foreignKey);
        $foreignKeyName = $foreignKey['name'] ?? $this->fallbackForeignKeyName();

        $this->assertConstraintNameCanBeUsed($foreignKeyName, $foreignKey);

        if ($columnState === 'initial' && $foreignKeyState === 'legacy') {
            $this->assertInitialState($foreignKeyName, $snapshot);

            return;
        }

        if ($foreignKey !== null) {
            $this->dropForeignKey($foreignKeyName);
        }

        if ($columnState === 'final') {
            try {
                DB::statement(sprintf(
                    'ALTER TABLE %s MODIFY COLUMN %s BIGINT UNSIGNED NULL DEFAULT NULL',
                    $this->quoteIdentifier($this->restructuringsTable),
                    $this->quoteIdentifier('company_id')
                ));
            } catch (Throwable $exception) {
                throw new RuntimeException(
                    'MySQL could not restore nullable restructurings.company_id '
                    .'while all verified loan/user foreign keys remained installed. '
                    .'No other foreign key or index was removed.',
                    0,
                    $exception
                );
            }
        }

        $this->addForeignKey($foreignKeyName, 'NO ACTION', 'SET NULL');
        $this->assertInitialState($foreignKeyName, $snapshot);
    }

    private function assertMySql(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException(
                'Hardening restructurings.company_id requires MySQL.'
            );
        }
    }

    private function assertRequiredStructure(): void
    {
        foreach ([
            $this->restructuringsTable => [
                'id',
                'company_id',
                'original_loan_id',
                'new_loan_id',
                'recorded_by',
            ],
            $this->companiesTable => ['id'],
            $this->loansTable => ['id', 'company_id'],
            $this->usersTable => ['id', 'company_id'],
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

        $columns = collect(Schema::getColumns($this->restructuringsTable))
            ->keyBy('name');
        $expectedNullable = [
            'original_loan_id' => false,
            'new_loan_id' => true,
            'recorded_by' => false,
        ];

        foreach ($expectedNullable as $name => $nullable) {
            $column = $columns->get($name);

            if (! is_array($column)
                || ! $this->isBigIntUnsigned($column)
                || (bool) ($column['nullable'] ?? false) !== $nullable) {
                throw new RuntimeException(
                    "Unexpected definition for restructurings.{$name}."
                );
            }
        }

        $companyId = $columns->get('company_id');
        $companyPrimaryKey = collect(Schema::getColumns($this->companiesTable))
            ->firstWhere('name', 'id');

        if (! is_array($companyId)
            || ! $this->isBigIntUnsigned($companyId)
            || ! is_array($companyPrimaryKey)
            || ! $this->isBigIntUnsigned($companyPrimaryKey)) {
            throw new RuntimeException(
                'restructurings.company_id and companies.id must be BIGINT UNSIGNED.'
            );
        }
    }

    private function assertDataIsConsistent(): void
    {
        $nullIds = DB::table($this->restructuringsTable)
            ->whereNull('company_id')
            ->orderBy('id')
            ->pluck('id');

        if ($nullIds->isNotEmpty()) {
            throw new RuntimeException(
                'restructurings.company_id contains NULL values for IDs: '
                .$nullIds->implode(', ').'.'
            );
        }

        $this->assertReferencedCompaniesExist();
        $this->assertTenantRelationIsConsistent(
            $this->loansTable,
            'original_loan_id',
            'original loan'
        );
        $this->assertTenantRelationIsConsistent(
            $this->loansTable,
            'new_loan_id',
            'new loan',
            true
        );
        $this->assertTenantRelationIsConsistent(
            $this->usersTable,
            'recorded_by',
            'recording user'
        );
    }

    private function assertReferencedCompaniesExist(): void
    {
        $orphanIds = DB::table("{$this->restructuringsTable} as restructuring")
            ->leftJoin(
                "{$this->companiesTable} as company",
                'company.id',
                '=',
                'restructuring.company_id'
            )
            ->whereNotNull('restructuring.company_id')
            ->whereNull('company.id')
            ->orderBy('restructuring.id')
            ->pluck('restructuring.id');

        if ($orphanIds->isNotEmpty()) {
            throw new RuntimeException(
                'Orphan company references found in restructurings: '
                .$orphanIds->implode(', ').'.'
            );
        }
    }

    private function assertTenantRelationIsConsistent(
        string $parentTable,
        string $foreignId,
        string $label,
        bool $nullable = false
    ): void {
        $query = DB::table("{$this->restructuringsTable} as restructuring")
            ->leftJoin(
                "{$parentTable} as parent",
                'parent.id',
                '=',
                "restructuring.{$foreignId}"
            );

        if ($nullable) {
            $query->whereNotNull("restructuring.{$foreignId}");
        }

        $orphanIds = (clone $query)
            ->whereNull('parent.id')
            ->orderBy('restructuring.id')
            ->pluck('restructuring.id');

        if ($orphanIds->isNotEmpty()) {
            throw new RuntimeException(
                "Restructurings with missing {$label} references: "
                .$orphanIds->implode(', ').'.'
            );
        }

        $crossCompanyIds = (clone $query)
            ->whereNotNull('parent.id')
            ->whereRaw('NOT (restructuring.company_id <=> parent.company_id)')
            ->orderBy('restructuring.id')
            ->pluck('restructuring.id');

        if ($crossCompanyIds->isNotEmpty()) {
            throw new RuntimeException(
                "Cross-company restructuring/{$label} references: "
                .$crossCompanyIds->implode(', ').'.'
            );
        }
    }

    private function columnState(): string
    {
        $column = $this->column('company_id');
        $definition = $this->companyIdDefinition();
        $nullable = (bool) ($column['nullable'] ?? false);
        $hasDefaultNull = preg_match('/\bDEFAULT\s+NULL\b/i', $definition) === 1;
        $hasAnyDefault = preg_match('/\bDEFAULT\b/i', $definition) === 1;

        if ($this->isBigIntUnsigned($column) && $nullable && $hasDefaultNull) {
            return 'initial';
        }

        if ($this->isBigIntUnsigned($column) && ! $nullable && ! $hasAnyDefault) {
            return 'final';
        }

        throw new RuntimeException(
            "Unexpected restructurings.company_id definition: {$definition}."
        );
    }

    private function companyIdDefinition(): string
    {
        $row = (array) DB::selectOne(
            'SHOW CREATE TABLE '.$this->quoteIdentifier($this->restructuringsTable)
        );
        $values = array_values($row);

        if (! isset($values[1])
            || ! is_string($values[1])
            || ! preg_match(
                '/^\s*`company_id`\s+(.+?)(?:,\s*)?$/mi',
                $values[1],
                $matches
            )) {
            throw new RuntimeException(
                'Unable to inspect restructurings.company_id with SHOW CREATE TABLE.'
            );
        }

        return trim($matches[1]);
    }

    private function companyForeignKey(): ?array
    {
        $foreignKeys = array_values(array_filter(
            Schema::getForeignKeys($this->restructuringsTable),
            fn (array $foreignKey): bool => $this->lowercaseColumns(
                $foreignKey['columns'] ?? []
            ) === ['company_id']
        ));

        if (count($foreignKeys) > 1) {
            throw new RuntimeException(
                'More than one simple foreign key uses restructurings.company_id.'
            );
        }

        if ($foreignKeys === []) {
            return null;
        }

        $foreignKey = $foreignKeys[0];

        if (strcasecmp(
            (string) ($foreignKey['foreign_table'] ?? ''),
            $this->companiesTable
        ) !== 0
            || $this->lowercaseColumns($foreignKey['foreign_columns'] ?? []) !== ['id']) {
            throw new RuntimeException(
                'The simple restructurings.company_id foreign key has an unexpected target.'
            );
        }

        return $foreignKey;
    }

    private function foreignKeyState(?array $foreignKey): string
    {
        if ($foreignKey === null) {
            return 'missing';
        }

        $update = strtoupper((string) ($foreignKey['on_update'] ?? ''));
        $delete = strtoupper((string) ($foreignKey['on_delete'] ?? ''));

        if (in_array($update, ['NO ACTION', 'RESTRICT'], true)
            && $delete === 'SET NULL') {
            return 'legacy';
        }

        if (in_array($update, ['NO ACTION', 'RESTRICT'], true)
            && in_array($delete, ['NO ACTION', 'RESTRICT'], true)) {
            return 'final';
        }

        throw new RuntimeException(
            'The restructurings.company_id foreign key has unexpected rules.'
        );
    }

    private function otherForeignKeySnapshot(): array
    {
        $foreignKeys = array_values(array_filter(
            Schema::getForeignKeys($this->restructuringsTable),
            fn (array $foreignKey): bool => $this->lowercaseColumns(
                $foreignKey['columns'] ?? []
            ) !== ['company_id']
        ));

        if (count($foreignKeys) !== count($this->expectedOtherForeignKeys)) {
            throw new RuntimeException(
                'Unexpected number of non-company foreign keys on restructurings.'
            );
        }

        $result = [];

        foreach ($this->expectedOtherForeignKeys as $name => $expected) {
            $matching = array_values(array_filter(
                $foreignKeys,
                fn (array $foreignKey): bool => strcasecmp(
                    (string) ($foreignKey['name'] ?? ''),
                    $name
                ) === 0
            ));

            if (count($matching) !== 1) {
                throw new RuntimeException(
                    "Expected restructurings foreign key {$name} exactly once."
                );
            }

            $foreignKey = $matching[0];

            if (! $this->matchesExpectedForeignKey($foreignKey, $expected)) {
                throw new RuntimeException(
                    "Restructurings foreign key {$name} has an unexpected definition."
                );
            }

            $result[strtolower($name)] = $this->normalizeForeignKey($foreignKey);
        }

        ksort($result);

        return $result;
    }

    private function matchesExpectedForeignKey(array $foreignKey, array $expected): bool
    {
        return $this->lowercaseColumns($foreignKey['columns'] ?? [])
                === array_map('strtolower', $expected['columns'])
            && strcasecmp(
                (string) ($foreignKey['foreign_table'] ?? ''),
                $expected['table']
            ) === 0
            && $this->lowercaseColumns($foreignKey['foreign_columns'] ?? [])
                === array_map('strtolower', $expected['foreign_columns'])
            && $this->normalizeReferentialRule($foreignKey['on_update'] ?? '')
                === $this->normalizeReferentialRule($expected['update'])
            && $this->normalizeReferentialRule($foreignKey['on_delete'] ?? '')
                === $this->normalizeReferentialRule($expected['delete']);
    }

    private function incomingForeignKeySnapshot(): array
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE as kcu')
            ->join('information_schema.REFERENTIAL_CONSTRAINTS as rc', function ($join): void {
                $join->on('rc.CONSTRAINT_SCHEMA', '=', 'kcu.CONSTRAINT_SCHEMA')
                    ->on('rc.TABLE_NAME', '=', 'kcu.TABLE_NAME')
                    ->on('rc.CONSTRAINT_NAME', '=', 'kcu.CONSTRAINT_NAME');
            })
            ->whereRaw('kcu.REFERENCED_TABLE_SCHEMA = DATABASE()')
            ->where('kcu.REFERENCED_TABLE_NAME', $this->restructuringsTable)
            ->orderBy('kcu.TABLE_NAME')
            ->orderBy('kcu.CONSTRAINT_NAME')
            ->orderBy('kcu.ORDINAL_POSITION')
            ->get([
                'kcu.TABLE_NAME',
                'kcu.CONSTRAINT_NAME',
                'kcu.COLUMN_NAME',
                'kcu.REFERENCED_COLUMN_NAME',
                'kcu.ORDINAL_POSITION',
                'rc.UPDATE_RULE',
                'rc.DELETE_RULE',
            ])
            ->map(fn (object $row): array => (array) $row)
            ->all();
    }

    private function normalizeForeignKey(array $foreignKey): array
    {
        return [
            'name' => strtolower((string) ($foreignKey['name'] ?? '')),
            'columns' => $this->lowercaseColumns($foreignKey['columns'] ?? []),
            'foreign_table' => strtolower((string) ($foreignKey['foreign_table'] ?? '')),
            'foreign_columns' => $this->lowercaseColumns(
                $foreignKey['foreign_columns'] ?? []
            ),
            'on_update' => strtoupper((string) ($foreignKey['on_update'] ?? '')),
            'on_delete' => strtoupper((string) ($foreignKey['on_delete'] ?? '')),
        ];
    }

    private function assertConstraintNameCanBeUsed(
        string $name,
        ?array $currentForeignKey
    ): void {
        if ($currentForeignKey !== null) {
            return;
        }

        $conflict = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->whereRaw('CONSTRAINT_SCHEMA = DATABASE()')
            ->whereRaw('LOWER(CONSTRAINT_NAME) = ?', [strtolower($name)])
            ->whereRaw('LOWER(TABLE_NAME) <> ?', [strtolower($this->restructuringsTable)])
            ->exists();

        if ($conflict) {
            throw new RuntimeException(
                "Constraint name {$name} is already used by another table."
            );
        }
    }

    private function fallbackForeignKeyName(): string
    {
        $name = strtolower($this->restructuringsTable.'_company_id_foreign');

        if (strlen($name) > 64) {
            throw new RuntimeException(
                'The conventional restructurings company foreign key name is too long.'
            );
        }

        return $name;
    }

    private function addForeignKey(
        string $name,
        string $onUpdate,
        string $onDelete
    ): void {
        DB::statement(sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) '
            .'REFERENCES %s (%s) ON UPDATE %s ON DELETE %s',
            $this->quoteIdentifier($this->restructuringsTable),
            $this->quoteIdentifier($name),
            $this->quoteIdentifier('company_id'),
            $this->quoteIdentifier($this->companiesTable),
            $this->quoteIdentifier('id'),
            $onUpdate,
            $onDelete
        ));
    }

    private function dropForeignKey(string $name): void
    {
        DB::statement(sprintf(
            'ALTER TABLE %s DROP FOREIGN KEY %s',
            $this->quoteIdentifier($this->restructuringsTable),
            $this->quoteIdentifier($name)
        ));
    }

    private function assertFinalState(string $foreignKeyName, array $snapshot): void
    {
        if ($this->columnState() !== 'final') {
            throw new RuntimeException(
                'restructurings.company_id did not reach NOT NULL without a default.'
            );
        }

        $this->assertExpectedForeignKey($foreignKeyName, 'final');
        $this->assertDataIsConsistent();
        $this->assertSnapshotUnchanged($snapshot);
    }

    private function assertInitialState(string $foreignKeyName, array $snapshot): void
    {
        if ($this->columnState() !== 'initial') {
            throw new RuntimeException(
                'restructurings.company_id was not restored as nullable DEFAULT NULL.'
            );
        }

        $this->assertExpectedForeignKey($foreignKeyName, 'legacy');
        $this->assertSnapshotUnchanged($snapshot);
    }

    private function assertExpectedForeignKey(string $name, string $state): void
    {
        $foreignKey = $this->companyForeignKey();

        if ($foreignKey === null
            || strcasecmp((string) ($foreignKey['name'] ?? ''), $name) !== 0
            || $this->foreignKeyState($foreignKey) !== $state) {
            throw new RuntimeException(
                "restructurings.company_id did not reach {$state} FK state."
            );
        }
    }

    private function snapshot(): array
    {
        return [
            'rows' => $this->rowSnapshot(),
            'other_columns' => $this->otherColumnSnapshot(),
            'indexes' => $this->indexSnapshot(),
            'other_foreign_keys' => $this->otherForeignKeySnapshot(),
            'incoming_foreign_keys' => $this->incomingForeignKeySnapshot(),
        ];
    }

    private function rowSnapshot(): array
    {
        $rows = DB::table($this->restructuringsTable)
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();

        return [
            'count' => count($rows),
            'hash' => hash('sha256', serialize($rows)),
            'company_ids' => array_column($rows, 'company_id'),
        ];
    }

    private function otherColumnSnapshot(): array
    {
        return DB::table('information_schema.COLUMNS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $this->restructuringsTable)
            ->where('COLUMN_NAME', '<>', 'company_id')
            ->orderBy('ORDINAL_POSITION')
            ->get([
                'COLUMN_NAME',
                'COLUMN_TYPE',
                'IS_NULLABLE',
                'COLUMN_DEFAULT',
                'EXTRA',
                'CHARACTER_SET_NAME',
                'COLLATION_NAME',
                'COLUMN_COMMENT',
                'GENERATION_EXPRESSION',
                'ORDINAL_POSITION',
            ])
            ->map(fn (object $column): array => (array) $column)
            ->all();
    }

    private function indexSnapshot(): array
    {
        $indexes = array_map(function (array $index): array {
            return [
                'name' => strtolower((string) ($index['name'] ?? '')),
                'columns' => $this->lowercaseColumns($index['columns'] ?? []),
                'type' => strtolower((string) ($index['type'] ?? '')),
                'unique' => (bool) ($index['unique'] ?? false),
                'primary' => (bool) ($index['primary'] ?? false),
            ];
        }, Schema::getIndexes($this->restructuringsTable));

        usort(
            $indexes,
            fn (array $left, array $right): int => $left['name'] <=> $right['name']
        );

        return $indexes;
    }

    private function assertSnapshotUnchanged(array $snapshot): void
    {
        if ($this->rowSnapshot() !== $snapshot['rows']) {
            throw new RuntimeException(
                'Restructuring rows changed while hardening company_id.'
            );
        }

        if ($this->otherColumnSnapshot() !== $snapshot['other_columns']) {
            throw new RuntimeException(
                'A restructurings column other than company_id changed.'
            );
        }

        if ($this->indexSnapshot() !== $snapshot['indexes']) {
            throw new RuntimeException(
                'Restructuring indexes changed while hardening company_id.'
            );
        }

        if ($this->otherForeignKeySnapshot() !== $snapshot['other_foreign_keys']) {
            throw new RuntimeException(
                'A non-company restructurings foreign key changed.'
            );
        }

        if ($this->incomingForeignKeySnapshot() !== $snapshot['incoming_foreign_keys']) {
            throw new RuntimeException(
                'An incoming restructurings foreign key changed.'
            );
        }
    }

    private function column(string $name): array
    {
        $column = collect(Schema::getColumns($this->restructuringsTable))
            ->firstWhere('name', $name);

        if (! is_array($column)) {
            throw new RuntimeException(
                "Unable to inspect {$this->restructuringsTable}.{$name}."
            );
        }

        return $column;
    }

    private function isBigIntUnsigned(array $column): bool
    {
        return strcasecmp((string) ($column['type_name'] ?? ''), 'bigint') === 0
            && preg_match('/\bunsigned\b/i', (string) ($column['type'] ?? '')) === 1;
    }

    private function normalizeReferentialRule(string $rule): string
    {
        $normalized = strtoupper(trim($rule));

        return $normalized === 'NO ACTION' ? 'RESTRICT' : $normalized;
    }

    private function lowercaseColumns(array $columns): array
    {
        return array_map('strtolower', $columns);
    }

    private function quoteIdentifier(string $identifier): string
    {
        if ($identifier === '' || str_contains($identifier, "\0")) {
            throw new RuntimeException('Invalid SQL identifier.');
        }

        return '`'.str_replace('`', '``', $identifier).'`';
    }
};
