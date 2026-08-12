<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    private string $documentsTable = 'customer_documents';

    private string $customersTable = 'customers';

    private string $companiesTable = 'companies';

    private string $customerTenantForeignKey = 'cust_docs_company_customer_tenant_fk';

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
                    $this->quoteIdentifier($this->documentsTable),
                    $this->quoteIdentifier('company_id')
                ));
            } catch (Throwable $exception) {
                throw new RuntimeException(
                    'MySQL could not make customer_documents.company_id NOT NULL '
                    .'while the verified CASCADE tenant foreign key remained '
                    .'installed. No other foreign key or index was removed; the '
                    .'missing simple company foreign key is a recoverable state.',
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
                    $this->quoteIdentifier($this->documentsTable),
                    $this->quoteIdentifier('company_id')
                ));
            } catch (Throwable $exception) {
                throw new RuntimeException(
                    'MySQL could not restore nullable customer_documents.company_id '
                    .'while the verified CASCADE tenant foreign key remained '
                    .'installed. No other foreign key or index was removed.',
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
                'Hardening customer_documents.company_id requires MySQL.'
            );
        }
    }

    private function assertRequiredStructure(): void
    {
        foreach ([
            $this->documentsTable => ['id', 'company_id', 'customer_id'],
            $this->customersTable => ['id', 'company_id'],
            $this->companiesTable => ['id'],
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
                'customer_documents.company_id and companies.id must both be BIGINT UNSIGNED.'
            );
        }
    }

    private function assertDataIsConsistent(): void
    {
        $nullIds = DB::table($this->documentsTable)
            ->whereNull('company_id')
            ->orderBy('id')
            ->pluck('id');

        if ($nullIds->isNotEmpty()) {
            throw new RuntimeException(
                'customer_documents.company_id contains NULL values for IDs: '
                .$nullIds->implode(', ').'.'
            );
        }

        $this->assertReferencedCompaniesExist();

        $orphanCustomerIds = DB::table("{$this->documentsTable} as document")
            ->leftJoin(
                "{$this->customersTable} as customer",
                'customer.id',
                '=',
                'document.customer_id'
            )
            ->whereNull('customer.id')
            ->orderBy('document.id')
            ->pluck('document.id');

        if ($orphanCustomerIds->isNotEmpty()) {
            throw new RuntimeException(
                'Customer documents with missing customers found: '
                .$orphanCustomerIds->implode(', ').'.'
            );
        }

        $crossCompanyIds = DB::table("{$this->documentsTable} as document")
            ->join(
                "{$this->customersTable} as customer",
                'customer.id',
                '=',
                'document.customer_id'
            )
            ->whereRaw('NOT (document.company_id <=> customer.company_id)')
            ->orderBy('document.id')
            ->pluck('document.id');

        if ($crossCompanyIds->isNotEmpty()) {
            throw new RuntimeException(
                'Cross-company customer/document references found: '
                .$crossCompanyIds->implode(', ').'.'
            );
        }
    }

    private function assertReferencedCompaniesExist(): void
    {
        $orphanIds = DB::table("{$this->documentsTable} as document")
            ->leftJoin(
                "{$this->companiesTable} as company",
                'company.id',
                '=',
                'document.company_id'
            )
            ->whereNotNull('document.company_id')
            ->whereNull('company.id')
            ->orderBy('document.id')
            ->pluck('document.id');

        if ($orphanIds->isNotEmpty()) {
            throw new RuntimeException(
                'Orphan company references found in customer_documents: '
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
            "Unexpected customer_documents.company_id definition: {$definition}."
        );
    }

    private function companyIdDefinition(): string
    {
        $row = (array) DB::selectOne(
            'SHOW CREATE TABLE '.$this->quoteIdentifier($this->documentsTable)
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
                'Unable to inspect customer_documents.company_id with SHOW CREATE TABLE.'
            );
        }

        return trim($matches[1]);
    }

    private function companyForeignKey(): ?array
    {
        $foreignKeys = array_values(array_filter(
            Schema::getForeignKeys($this->documentsTable),
            fn (array $foreignKey): bool => $this->lowercaseColumns(
                $foreignKey['columns'] ?? []
            ) === ['company_id']
        ));

        if (count($foreignKeys) > 1) {
            throw new RuntimeException(
                'More than one simple foreign key uses customer_documents.company_id.'
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
                'The simple customer_documents.company_id foreign key has an unexpected target.'
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
            'The customer_documents.company_id foreign key has unexpected rules.'
        );
    }

    private function otherForeignKeySnapshot(): array
    {
        $foreignKeys = array_values(array_filter(
            Schema::getForeignKeys($this->documentsTable),
            fn (array $foreignKey): bool => $this->lowercaseColumns(
                $foreignKey['columns'] ?? []
            ) !== ['company_id']
        ));
        $tenantMatches = array_values(array_filter(
            $foreignKeys,
            fn (array $foreignKey): bool => strcasecmp(
                (string) ($foreignKey['name'] ?? ''),
                $this->customerTenantForeignKey
            ) === 0
        ));

        if (count($tenantMatches) !== 1) {
            throw new RuntimeException(
                "Expected {$this->customerTenantForeignKey} exactly once."
            );
        }

        $tenant = $tenantMatches[0];

        if ($this->lowercaseColumns($tenant['columns'] ?? [])
                !== ['company_id', 'customer_id']
            || strcasecmp(
                (string) ($tenant['foreign_table'] ?? ''),
                $this->customersTable
            ) !== 0
            || $this->lowercaseColumns($tenant['foreign_columns'] ?? [])
                !== ['company_id', 'id']
            || $this->normalizeReferentialRule($tenant['on_update'] ?? '') !== 'RESTRICT'
            || strtoupper((string) ($tenant['on_delete'] ?? '')) !== 'CASCADE') {
            throw new RuntimeException(
                "{$this->customerTenantForeignKey} must remain UPDATE RESTRICT / DELETE CASCADE."
            );
        }

        $normalized = array_map(
            fn (array $foreignKey): array => $this->normalizeForeignKey($foreignKey),
            $foreignKeys
        );
        usort(
            $normalized,
            fn (array $left, array $right): int => $left['name'] <=> $right['name']
        );

        return $normalized;
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
            ->where('kcu.REFERENCED_TABLE_NAME', $this->documentsTable)
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
            ->whereRaw('LOWER(TABLE_NAME) <> ?', [strtolower($this->documentsTable)])
            ->exists();

        if ($conflict) {
            throw new RuntimeException(
                "Constraint name {$name} is already used by another table."
            );
        }
    }

    private function fallbackForeignKeyName(): string
    {
        $name = strtolower($this->documentsTable.'_company_id_foreign');

        if (strlen($name) > 64) {
            throw new RuntimeException(
                'The conventional customer_documents company foreign key name is too long.'
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
            $this->quoteIdentifier($this->documentsTable),
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
            $this->quoteIdentifier($this->documentsTable),
            $this->quoteIdentifier($name)
        ));
    }

    private function assertFinalState(string $foreignKeyName, array $snapshot): void
    {
        if ($this->columnState() !== 'final') {
            throw new RuntimeException(
                'customer_documents.company_id did not reach NOT NULL without a default.'
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
                'customer_documents.company_id was not restored as nullable DEFAULT NULL.'
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
                "customer_documents.company_id did not reach {$state} FK state."
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
        $rows = DB::table($this->documentsTable)
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
            ->where('TABLE_NAME', $this->documentsTable)
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
        }, Schema::getIndexes($this->documentsTable));

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
                'Customer document rows changed while hardening company_id.'
            );
        }

        if ($this->otherColumnSnapshot() !== $snapshot['other_columns']) {
            throw new RuntimeException(
                'A customer_documents column other than company_id changed.'
            );
        }

        if ($this->indexSnapshot() !== $snapshot['indexes']) {
            throw new RuntimeException(
                'Customer document indexes changed while hardening company_id.'
            );
        }

        if ($this->otherForeignKeySnapshot() !== $snapshot['other_foreign_keys']) {
            throw new RuntimeException(
                'A customer_documents foreign key other than the company FK changed.'
            );
        }

        if ($this->incomingForeignKeySnapshot() !== $snapshot['incoming_foreign_keys']) {
            throw new RuntimeException(
                'An incoming customer_documents foreign key changed.'
            );
        }
    }

    private function column(string $name): array
    {
        $column = collect(Schema::getColumns($this->documentsTable))
            ->firstWhere('name', $name);

        if (! is_array($column)) {
            throw new RuntimeException(
                "Unable to inspect {$this->documentsTable}.{$name}."
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
