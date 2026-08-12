<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    private string $settingsTable = 'settings';

    private string $companiesTable = 'companies';

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

        $this->assertUniqueCompanyKeyIndex($snapshot['indexes']);
        $this->assertRecoverableState($columnState, $foreignKeyState, 'up');
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
                $this->quoteIdentifier($this->settingsTable),
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

        $this->assertUniqueCompanyKeyIndex($snapshot['indexes']);
        $this->assertRecoverableState($columnState, $foreignKeyState, 'down');
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
                $this->quoteIdentifier($this->settingsTable),
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
                'Hardening settings.company_id requires MySQL.'
            );
        }
    }

    private function assertRequiredStructure(): void
    {
        foreach ([
            $this->settingsTable => ['id', 'company_id', 'key'],
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
                'settings.company_id and companies.id must both be BIGINT UNSIGNED.'
            );
        }
    }

    private function assertDataIsConsistent(): void
    {
        $nullIds = DB::table($this->settingsTable)
            ->whereNull('company_id')
            ->orderBy('id')
            ->pluck('id');

        if ($nullIds->isNotEmpty()) {
            throw new RuntimeException(
                'settings.company_id contains NULL values for IDs: '
                .$nullIds->implode(', ').'.'
            );
        }

        $this->assertReferencedCompaniesExist();
    }

    private function assertReferencedCompaniesExist(): void
    {
        $settings = $this->quoteIdentifier($this->settingsTable);
        $companies = $this->quoteIdentifier($this->companiesTable);

        $orphanIds = DB::table("{$this->settingsTable} as setting")
            ->leftJoin("{$this->companiesTable} as company", 'company.id', '=', 'setting.company_id')
            ->whereNotNull('setting.company_id')
            ->whereNull('company.id')
            ->orderBy('setting.id')
            ->pluck('setting.id');

        if ($orphanIds->isNotEmpty()) {
            throw new RuntimeException(
                "Orphan company references found in {$settings} against {$companies}: "
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
            "Unexpected settings.company_id definition: {$definition}."
        );
    }

    private function companyIdDefinition(): string
    {
        $createSql = $this->showCreateTable();

        if (! preg_match(
            '/^\s*`company_id`\s+(.+?)(?:,\s*)?$/mi',
            $createSql,
            $matches
        )) {
            throw new RuntimeException(
                'Unable to read the settings.company_id definition from SHOW CREATE TABLE.'
            );
        }

        return trim($matches[1]);
    }

    private function showCreateTable(): string
    {
        $row = (array) DB::selectOne(
            'SHOW CREATE TABLE '.$this->quoteIdentifier($this->settingsTable)
        );
        $values = array_values($row);

        if (! isset($values[1]) || ! is_string($values[1])) {
            throw new RuntimeException(
                "Unable to read SHOW CREATE TABLE for {$this->settingsTable}."
            );
        }

        return $values[1];
    }

    private function companyForeignKey(): ?array
    {
        $foreignKeys = array_values(array_filter(
            Schema::getForeignKeys($this->settingsTable),
            fn (array $foreignKey): bool => in_array(
                'company_id',
                $this->lowercaseColumns($foreignKey['columns'] ?? []),
                true
            )
        ));

        if (count($foreignKeys) > 1) {
            throw new RuntimeException(
                'More than one foreign key uses settings.company_id.'
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
                'The foreign key using settings.company_id has an unexpected target.'
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
        $restrictedUpdates = ['NO ACTION', 'RESTRICT'];

        if (in_array($update, $restrictedUpdates, true) && $delete === 'SET NULL') {
            return 'legacy';
        }

        if (in_array($update, $restrictedUpdates, true)
            && in_array($delete, ['NO ACTION', 'RESTRICT'], true)) {
            return 'final';
        }

        throw new RuntimeException(
            'The settings.company_id foreign key has unexpected update/delete rules.'
        );
    }

    private function assertRecoverableState(
        string $columnState,
        string $foreignKeyState,
        string $direction
    ): void {
        if ($columnState === 'final' && $foreignKeyState === 'legacy') {
            throw new RuntimeException(
                'Unsafe partial state: NOT NULL settings.company_id still has a SET NULL foreign key.'
            );
        }

        if (! in_array($direction, ['up', 'down'], true)) {
            throw new RuntimeException("Unknown migration direction {$direction}.");
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
            ->whereRaw('LOWER(TABLE_NAME) <> ?', [strtolower($this->settingsTable)])
            ->exists();

        if ($conflict) {
            throw new RuntimeException(
                "Constraint name {$name} is already used by another table."
            );
        }
    }

    private function fallbackForeignKeyName(): string
    {
        $name = strtolower($this->settingsTable.'_company_id_foreign');

        if (strlen($name) > 64) {
            throw new RuntimeException(
                'The conventional settings.company_id foreign key name exceeds 64 characters.'
            );
        }

        return $name;
    }

    private function assertUniqueCompanyKeyIndex(array $indexes): void
    {
        $matching = array_values(array_filter(
            $indexes,
            fn (array $index): bool => (bool) ($index['unique'] ?? false)
                && $this->lowercaseColumns($index['columns'] ?? []) === ['company_id', 'key']
        ));

        if (count($matching) !== 1) {
            throw new RuntimeException(
                'Expected exactly one UNIQUE index on settings(company_id, key).'
            );
        }
    }

    private function addForeignKey(
        string $name,
        string $onUpdate,
        string $onDelete
    ): void {
        DB::statement(sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) '
            .'REFERENCES %s (%s) ON UPDATE %s ON DELETE %s',
            $this->quoteIdentifier($this->settingsTable),
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
            $this->quoteIdentifier($this->settingsTable),
            $this->quoteIdentifier($name)
        ));
    }

    private function assertFinalState(string $foreignKeyName, array $snapshot): void
    {
        if ($this->columnState() !== 'final') {
            throw new RuntimeException(
                'settings.company_id did not reach BIGINT UNSIGNED NOT NULL without a default.'
            );
        }

        $this->assertExpectedForeignKey($foreignKeyName, 'final');
        $this->assertSnapshotUnchanged($snapshot);
    }

    private function assertInitialState(string $foreignKeyName, array $snapshot): void
    {
        if ($this->columnState() !== 'initial') {
            throw new RuntimeException(
                'settings.company_id was not restored as BIGINT UNSIGNED NULL DEFAULT NULL.'
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
                "settings.company_id did not reach the expected {$state} foreign key state."
            );
        }
    }

    private function snapshot(): array
    {
        return [
            'rows' => $this->rowSnapshot(),
            'other_columns' => $this->otherColumnSnapshot(),
            'indexes' => $this->indexSnapshot(),
        ];
    }

    private function rowSnapshot(): array
    {
        $rows = DB::table($this->settingsTable)
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();

        return [
            'count' => count($rows),
            'hash' => hash('sha256', serialize($rows)),
            'company_ids' => array_map(
                fn (array $row): mixed => $row['company_id'],
                $rows
            ),
        ];
    }

    private function otherColumnSnapshot(): array
    {
        return DB::table('information_schema.COLUMNS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $this->settingsTable)
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
        }, Schema::getIndexes($this->settingsTable));

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
                'The settings rows changed while hardening company_id.'
            );
        }

        if ($this->otherColumnSnapshot() !== $snapshot['other_columns']) {
            throw new RuntimeException(
                'A settings column other than company_id changed.'
            );
        }

        $currentIndexes = $this->indexSnapshot();
        $this->assertUniqueCompanyKeyIndex($currentIndexes);

        if ($currentIndexes !== $snapshot['indexes']) {
            throw new RuntimeException(
                'The settings indexes changed while hardening company_id.'
            );
        }
    }

    private function column(string $name): array
    {
        $column = collect(Schema::getColumns($this->settingsTable))
            ->firstWhere('name', $name);

        if (! is_array($column)) {
            throw new RuntimeException(
                "Unable to inspect {$this->settingsTable}.{$name}."
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
