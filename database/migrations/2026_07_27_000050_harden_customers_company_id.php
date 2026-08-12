<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    private string $customersTable = 'customers';

    private string $companiesTable = 'companies';

    private string $loansTable = 'loans';

    private string $documentsTable = 'customer_documents';

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

        $this->assertUniqueCompanyIdIndex($snapshot['indexes']);
        $this->assertRecoverableState($columnState, $foreignKeyState);
        $this->assertConstraintNameCanBeUsed($foreignKeyName, $foreignKey);

        if ($columnState === 'final' && $foreignKeyState === 'final') {
            $this->assertFinalState($foreignKeyName, $snapshot);

            return;
        }

        if ($foreignKey !== null) {
            $this->dropForeignKey($foreignKeyName);
        }

        if ($columnState === 'initial') {
            DB::statement(sprintf(
                'ALTER TABLE %s MODIFY COLUMN %s BIGINT UNSIGNED NOT NULL',
                $this->quoteIdentifier($this->customersTable),
                $this->quoteIdentifier('company_id')
            ));
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

        $this->assertUniqueCompanyIdIndex($snapshot['indexes']);
        $this->assertRecoverableState($columnState, $foreignKeyState);
        $this->assertConstraintNameCanBeUsed($foreignKeyName, $foreignKey);

        if ($columnState === 'initial' && $foreignKeyState === 'legacy') {
            $this->assertInitialState($foreignKeyName, $snapshot);

            return;
        }

        if ($foreignKey !== null) {
            $this->dropForeignKey($foreignKeyName);
        }

        if ($columnState === 'final') {
            DB::statement(sprintf(
                'ALTER TABLE %s MODIFY COLUMN %s BIGINT UNSIGNED NULL DEFAULT NULL',
                $this->quoteIdentifier($this->customersTable),
                $this->quoteIdentifier('company_id')
            ));
        }

        $this->addForeignKey($foreignKeyName, 'NO ACTION', 'SET NULL');
        $this->assertInitialState($foreignKeyName, $snapshot);
    }

    private function assertMySql(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException(
                'Hardening customers.company_id requires MySQL.'
            );
        }
    }

    private function assertRequiredStructure(): void
    {
        foreach ([
            $this->customersTable => ['id', 'company_id'],
            $this->companiesTable => ['id'],
            $this->loansTable => ['company_id', 'customer_id'],
            $this->documentsTable => ['company_id', 'customer_id'],
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

        $companyId = $this->column('company_id');
        $companyPrimaryKey = collect(Schema::getColumns($this->companiesTable))
            ->firstWhere('name', 'id');

        if (! $this->isBigIntUnsigned($companyId)
            || ! is_array($companyPrimaryKey)
            || ! $this->isBigIntUnsigned($companyPrimaryKey)) {
            throw new RuntimeException(
                'customers.company_id and companies.id must both be BIGINT UNSIGNED.'
            );
        }
    }

    private function assertDataIsConsistent(): void
    {
        $nullIds = DB::table($this->customersTable)
            ->whereNull('company_id')
            ->orderBy('id')
            ->pluck('id');

        if ($nullIds->isNotEmpty()) {
            throw new RuntimeException(
                'customers.company_id contains NULL values for IDs: '
                .$nullIds->implode(', ').'.'
            );
        }

        $this->assertReferencedCompaniesExist();
    }

    private function assertReferencedCompaniesExist(): void
    {
        $orphanIds = DB::table("{$this->customersTable} as customer")
            ->leftJoin(
                "{$this->companiesTable} as company",
                'company.id',
                '=',
                'customer.company_id'
            )
            ->whereNotNull('customer.company_id')
            ->whereNull('company.id')
            ->orderBy('customer.id')
            ->pluck('customer.id');

        if ($orphanIds->isNotEmpty()) {
            throw new RuntimeException(
                'Orphan company references found in customers: '
                .$orphanIds->implode(', ').'.'
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
            "Unexpected customers.company_id definition: {$definition}."
        );
    }

    private function companyIdDefinition(): string
    {
        $row = (array) DB::selectOne(
            'SHOW CREATE TABLE '.$this->quoteIdentifier($this->customersTable)
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
                'Unable to inspect customers.company_id with SHOW CREATE TABLE.'
            );
        }

        return trim($matches[1]);
    }

    private function companyForeignKey(): ?array
    {
        $foreignKeys = array_values(array_filter(
            Schema::getForeignKeys($this->customersTable),
            fn (array $foreignKey): bool => in_array(
                'company_id',
                $this->lowercaseColumns($foreignKey['columns'] ?? []),
                true
            )
        ));

        if (count($foreignKeys) > 1) {
            throw new RuntimeException(
                'More than one foreign key uses customers.company_id.'
            );
        }

        if ($foreignKeys === []) {
            return null;
        }

        $foreignKey = $foreignKeys[0];

        if ($this->lowercaseColumns($foreignKey['columns'] ?? []) !== ['company_id']
            || strcasecmp(
                (string) ($foreignKey['foreign_table'] ?? ''),
                $this->companiesTable
            ) !== 0
            || $this->lowercaseColumns($foreignKey['foreign_columns'] ?? []) !== ['id']) {
            throw new RuntimeException(
                'The foreign key using customers.company_id has an unexpected target.'
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
            'The customers.company_id foreign key has unexpected update/delete rules.'
        );
    }

    private function assertRecoverableState(
        string $columnState,
        string $foreignKeyState
    ): void {
        if ($columnState === 'final' && $foreignKeyState === 'legacy') {
            throw new RuntimeException(
                'Unsafe partial state: NOT NULL customers.company_id has a SET NULL foreign key.'
            );
        }
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
            ->whereRaw('LOWER(TABLE_NAME) <> ?', [strtolower($this->customersTable)])
            ->exists();

        if ($conflict) {
            throw new RuntimeException(
                "Constraint name {$name} is already used by another table."
            );
        }
    }

    private function fallbackForeignKeyName(): string
    {
        $name = strtolower($this->customersTable.'_company_id_foreign');

        if (strlen($name) > 64) {
            throw new RuntimeException(
                'The conventional customers.company_id foreign key name exceeds 64 characters.'
            );
        }

        return $name;
    }

    private function assertUniqueCompanyIdIndex(array $indexes): void
    {
        $matching = array_values(array_filter(
            $indexes,
            fn (array $index): bool => (bool) ($index['unique'] ?? false)
                && $this->lowercaseColumns($index['columns'] ?? [])
                    === ['company_id', 'id']
        ));

        if (count($matching) !== 1) {
            throw new RuntimeException(
                'Expected exactly one UNIQUE index on customers(company_id, id).'
            );
        }
    }

    private function dependentForeignKeySnapshot(): array
    {
        $expected = [
            $this->loansTable => [
                'columns' => ['company_id', 'customer_id'],
                'delete' => ['RESTRICT', 'NO ACTION'],
            ],
            $this->documentsTable => [
                'columns' => ['company_id', 'customer_id'],
                'delete' => ['CASCADE'],
            ],
        ];
        $result = [];

        foreach ($expected as $table => $definition) {
            $matching = array_values(array_filter(
                Schema::getForeignKeys($table),
                fn (array $foreignKey): bool => $this->lowercaseColumns(
                    $foreignKey['columns'] ?? []
                ) === $definition['columns']
                    && strcasecmp(
                        (string) ($foreignKey['foreign_table'] ?? ''),
                        $this->customersTable
                    ) === 0
                    && $this->lowercaseColumns($foreignKey['foreign_columns'] ?? [])
                        === ['company_id', 'id']
            ));

            if (count($matching) !== 1) {
                throw new RuntimeException(
                    "Expected exactly one tenant customer foreign key on {$table}."
                );
            }

            $foreignKey = $matching[0];
            $update = strtoupper((string) ($foreignKey['on_update'] ?? ''));
            $delete = strtoupper((string) ($foreignKey['on_delete'] ?? ''));

            if (! in_array($update, ['RESTRICT', 'NO ACTION'], true)
                || ! in_array($delete, $definition['delete'], true)) {
                throw new RuntimeException(
                    "The tenant customer foreign key on {$table} has unexpected rules."
                );
            }

            $result[$table] = $this->normalizeForeignKey($foreignKey);
        }

        ksort($result);

        return $result;
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

    private function addForeignKey(
        string $name,
        string $onUpdate,
        string $onDelete
    ): void {
        DB::statement(sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) '
            .'REFERENCES %s (%s) ON UPDATE %s ON DELETE %s',
            $this->quoteIdentifier($this->customersTable),
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
            $this->quoteIdentifier($this->customersTable),
            $this->quoteIdentifier($name)
        ));
    }

    private function assertFinalState(string $foreignKeyName, array $snapshot): void
    {
        if ($this->columnState() !== 'final') {
            throw new RuntimeException(
                'customers.company_id did not reach BIGINT UNSIGNED NOT NULL without a default.'
            );
        }

        $this->assertExpectedForeignKey($foreignKeyName, 'final');
        $this->assertSnapshotUnchanged($snapshot);
    }

    private function assertInitialState(string $foreignKeyName, array $snapshot): void
    {
        if ($this->columnState() !== 'initial') {
            throw new RuntimeException(
                'customers.company_id was not restored as BIGINT UNSIGNED NULL DEFAULT NULL.'
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
                "customers.company_id did not reach the expected {$state} foreign key state."
            );
        }
    }

    private function snapshot(): array
    {
        return [
            'rows' => $this->rowSnapshot(),
            'other_columns' => $this->otherColumnSnapshot(),
            'indexes' => $this->indexSnapshot(),
            'dependent_foreign_keys' => $this->dependentForeignKeySnapshot(),
        ];
    }

    private function rowSnapshot(): array
    {
        $rows = DB::table($this->customersTable)
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();

        return [
            'count' => count($rows),
            'hash' => hash('sha256', serialize($rows)),
        ];
    }

    private function otherColumnSnapshot(): array
    {
        return DB::table('information_schema.COLUMNS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $this->customersTable)
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
        }, Schema::getIndexes($this->customersTable));

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
                'Customer rows changed while hardening company_id.'
            );
        }

        if ($this->otherColumnSnapshot() !== $snapshot['other_columns']) {
            throw new RuntimeException(
                'A customers column other than company_id changed.'
            );
        }

        $indexes = $this->indexSnapshot();
        $this->assertUniqueCompanyIdIndex($indexes);

        if ($indexes !== $snapshot['indexes']) {
            throw new RuntimeException(
                'Customer indexes changed while hardening company_id.'
            );
        }

        if ($this->dependentForeignKeySnapshot()
            !== $snapshot['dependent_foreign_keys']) {
            throw new RuntimeException(
                'A dependent tenant customer foreign key changed.'
            );
        }
    }

    private function column(string $name): array
    {
        $column = collect(Schema::getColumns($this->customersTable))
            ->firstWhere('name', $name);

        if (! is_array($column)) {
            throw new RuntimeException(
                "Unable to inspect {$this->customersTable}.{$name}."
            );
        }

        return $column;
    }

    private function isBigIntUnsigned(array $column): bool
    {
        return strcasecmp((string) ($column['type_name'] ?? ''), 'bigint') === 0
            && preg_match('/\bunsigned\b/i', (string) ($column['type'] ?? '')) === 1;
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
