<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    private const SIMPLE_CONSTRAINT = 'restructurings_new_loan_id_foreign';
    private const TENANT_CONSTRAINT = 'restruct_new_loan_tenant_fk';

    public function up(): void
    {
        $this->assertRequiredStructure();
        $this->assertDataIsConsistent();

        $foreignKeys = Schema::getForeignKeys('restructurings');
        $this->assertNoUnexpectedForeignKeys($foreignKeys);

        $simple = $this->findForeignKey($foreignKeys, self::SIMPLE_CONSTRAINT);
        $tenant = $this->findForeignKey($foreignKeys, self::TENANT_CONSTRAINT);

        if ($simple !== null
            && ! $this->isLegacySimpleForeignKey($simple)
            && ! $this->isRestrictedSimpleForeignKey($simple)) {
            throw new \RuntimeException(
                'The existing restructurings.new_loan_id foreign key has an unexpected definition.'
            );
        }

        if ($tenant !== null && ! $this->isTenantForeignKey($tenant)) {
            throw new \RuntimeException(
                'The existing restructuring tenant foreign key has an unexpected definition.'
            );
        }

        if ($simple !== null && $this->isLegacySimpleForeignKey($simple)) {
            Schema::table('restructurings', function (Blueprint $table): void {
                $table->dropForeign(self::SIMPLE_CONSTRAINT);
            });
            $simple = null;
        }

        if ($simple === null) {
            Schema::table('restructurings', function (Blueprint $table): void {
                $table->foreign('new_loan_id', self::SIMPLE_CONSTRAINT)
                    ->references('id')->on('loans')
                    ->restrictOnUpdate()
                    ->restrictOnDelete();
            });
        }

        if ($tenant === null) {
            Schema::table('restructurings', function (Blueprint $table): void {
                $table->foreign(
                    ['company_id', 'new_loan_id'],
                    self::TENANT_CONSTRAINT
                )
                    ->references(['company_id', 'id'])->on('loans')
                    ->restrictOnUpdate()
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        $this->assertRequiredStructure();

        $foreignKeys = Schema::getForeignKeys('restructurings');
        $this->assertNoUnexpectedForeignKeys($foreignKeys);

        $simple = $this->findForeignKey($foreignKeys, self::SIMPLE_CONSTRAINT);
        $tenant = $this->findForeignKey($foreignKeys, self::TENANT_CONSTRAINT);

        if ($tenant !== null && ! $this->isTenantForeignKey($tenant)) {
            throw new \RuntimeException(
                'The existing restructuring tenant foreign key has an unexpected definition.'
            );
        }

        if ($simple !== null
            && ! $this->isLegacySimpleForeignKey($simple)
            && ! $this->isRestrictedSimpleForeignKey($simple)) {
            throw new \RuntimeException(
                'The existing restructurings.new_loan_id foreign key has an unexpected definition.'
            );
        }

        if ($tenant !== null) {
            Schema::table('restructurings', function (Blueprint $table): void {
                $table->dropForeign(self::TENANT_CONSTRAINT);
            });
        }

        if ($simple !== null && $this->isRestrictedSimpleForeignKey($simple)) {
            Schema::table('restructurings', function (Blueprint $table): void {
                $table->dropForeign(self::SIMPLE_CONSTRAINT);
            });
            $simple = null;
        }

        if ($simple === null) {
            Schema::table('restructurings', function (Blueprint $table): void {
                $table->foreign('new_loan_id', self::SIMPLE_CONSTRAINT)
                    ->references('id')->on('loans')
                    ->noActionOnUpdate()
                    ->nullOnDelete();
            });
        }
    }

    private function assertRequiredStructure(): void
    {
        foreach ([
            'restructurings' => ['company_id', 'new_loan_id'],
            'loans' => ['company_id', 'id'],
        ] as $table => $columns) {
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    throw new \RuntimeException("Missing required column {$table}.{$column}.");
                }
            }
        }

        if (! $this->hasExactIndex(
            'restructurings',
            ['company_id', 'new_loan_id'],
            false,
            'restruct_company_new_loan_idx'
        )) {
            throw new \RuntimeException(
                'Missing exact index restruct_company_new_loan_idx on '
                .'restructurings(company_id, new_loan_id).'
            );
        }

        if (! $this->hasExactIndex('loans', ['company_id', 'id'], true)) {
            throw new \RuntimeException(
                'Missing exact unique index on loans(company_id, id).'
            );
        }
    }

    private function assertDataIsConsistent(): void
    {
        $crossCompanyIds = DB::table('restructurings as r')
            ->join('loans as l', 'l.id', '=', 'r.new_loan_id')
            ->whereNotNull('r.new_loan_id')
            ->whereRaw('NOT (r.company_id <=> l.company_id)')
            ->pluck('r.id');

        if ($crossCompanyIds->isNotEmpty()) {
            throw new \RuntimeException(
                'Cross-company new loan references found in restructurings: '
                .$crossCompanyIds->implode(', ')
            );
        }

        $orphanIds = DB::table('restructurings as r')
            ->leftJoin('loans as l', 'l.id', '=', 'r.new_loan_id')
            ->whereNotNull('r.new_loan_id')
            ->whereNull('l.id')
            ->pluck('r.id');

        if ($orphanIds->isNotEmpty()) {
            throw new \RuntimeException(
                'Orphan new loan references found in restructurings: '
                .$orphanIds->implode(', ')
            );
        }
    }

    private function assertNoUnexpectedForeignKeys(array $foreignKeys): void
    {
        foreach ($foreignKeys as $foreignKey) {
            $columns = array_map('strtolower', $foreignKey['columns'] ?? []);

            if (! in_array('new_loan_id', $columns, true)) {
                continue;
            }

            $name = strtolower((string) ($foreignKey['name'] ?? ''));
            $allowed = array_map('strtolower', [
                self::SIMPLE_CONSTRAINT,
                self::TENANT_CONSTRAINT,
            ]);

            if (! in_array($name, $allowed, true)) {
                throw new \RuntimeException(
                    "Unexpected foreign key {$name} uses restructurings.new_loan_id."
                );
            }
        }
    }

    private function findForeignKey(array $foreignKeys, string $name): ?array
    {
        foreach ($foreignKeys as $foreignKey) {
            if (strcasecmp((string) ($foreignKey['name'] ?? ''), $name) === 0) {
                return $foreignKey;
            }
        }

        return null;
    }

    private function isLegacySimpleForeignKey(array $foreignKey): bool
    {
        return $this->matchesForeignKey(
            $foreignKey,
            ['new_loan_id'],
            'loans',
            ['id'],
            ['NO ACTION', 'RESTRICT'],
            ['SET NULL']
        );
    }

    private function isRestrictedSimpleForeignKey(array $foreignKey): bool
    {
        return $this->matchesForeignKey(
            $foreignKey,
            ['new_loan_id'],
            'loans',
            ['id'],
            ['NO ACTION', 'RESTRICT'],
            ['RESTRICT', 'NO ACTION']
        );
    }

    private function isTenantForeignKey(array $foreignKey): bool
    {
        return $this->matchesForeignKey(
            $foreignKey,
            ['company_id', 'new_loan_id'],
            'loans',
            ['company_id', 'id'],
            ['RESTRICT', 'NO ACTION'],
            ['RESTRICT', 'NO ACTION']
        );
    }

    private function matchesForeignKey(
        array $foreignKey,
        array $columns,
        string $foreignTable,
        array $foreignColumns,
        array $updateRules,
        array $deleteRules
    ): bool {
        return $this->sameColumns($foreignKey['columns'] ?? [], $columns)
            && strcasecmp((string) ($foreignKey['foreign_table'] ?? ''), $foreignTable) === 0
            && $this->sameColumns($foreignKey['foreign_columns'] ?? [], $foreignColumns)
            && in_array(strtoupper((string) ($foreignKey['on_update'] ?? '')), $updateRules, true)
            && in_array(strtoupper((string) ($foreignKey['on_delete'] ?? '')), $deleteRules, true);
    }

    private function hasExactIndex(
        string $table,
        array $columns,
        bool $unique,
        ?string $name = null
    ): bool {
        foreach (Schema::getIndexes($table) as $index) {
            if ($name !== null
                && strcasecmp((string) ($index['name'] ?? ''), $name) !== 0) {
                continue;
            }

            if ((bool) ($index['unique'] ?? false) === $unique
                && $this->sameColumns($index['columns'] ?? [], $columns)) {
                return true;
            }
        }

        return false;
    }

    private function sameColumns(array $actual, array $expected): bool
    {
        return array_map('strtolower', $actual) === array_map('strtolower', $expected);
    }
};
