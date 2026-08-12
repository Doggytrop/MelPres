<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    private const INDEXES = [
        'customers' => [
            'name' => 'customers_company_id_id_unique',
            'columns' => ['company_id', 'id'],
        ],
        'loans' => [
            'name' => 'loans_company_id_id_unique',
            'columns' => ['company_id', 'id'],
        ],
        'users' => [
            'name' => 'users_company_id_id_unique',
            'columns' => ['company_id', 'id'],
        ],
    ];

    private const COMPANY_SUPPORT_INDEXES = [
        'customers' => 'customers_company_id_foreign',
        'loans' => 'loans_company_id_foreign',
        'users' => 'users_company_id_foreign',
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $index) {
            if ($this->hasEquivalentIndex($table, $index['columns'], true)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($index) {
                $blueprint->unique($index['columns'], $index['name']);
            });
        }

        $this->dropRedundantCompanySupportIndexes();
    }

    public function down(): void
    {
        $this->restoreCompanySupportIndexes();

        foreach (array_reverse(self::INDEXES, true) as $table => $index) {
            if (! $this->hasNamedIndex($table, $index['name'])) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($index) {
                $blueprint->dropUnique($index['name']);
            });
        }
    }

    private function restoreCompanySupportIndexes(): void
    {
        foreach (self::COMPANY_SUPPORT_INDEXES as $table => $name) {
            if ($this->hasExactIndex($table, ['company_id'])) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($name) {
                $blueprint->index(['company_id'], $name);
            });
        }
    }

    private function dropRedundantCompanySupportIndexes(): void
    {
        foreach (self::COMPANY_SUPPORT_INDEXES as $table => $name) {
            if (! $this->hasNamedExactIndex($table, $name, ['company_id'])) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($name) {
                $blueprint->dropIndex($name);
            });
        }
    }

    private function hasEquivalentIndex(string $table, array $columns, bool $unique): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($this->sameColumns($index['columns'], $columns)
                && (bool) $index['unique'] === $unique) {
                return true;
            }
        }

        return false;
    }

    private function hasNamedIndex(string $table, string $name): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (strcasecmp($index['name'], $name) === 0) {
                return true;
            }
        }

        return false;
    }

    private function hasExactIndex(string $table, array $columns): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($this->sameColumns($index['columns'], $columns)) {
                return true;
            }
        }

        return false;
    }

    private function hasNamedExactIndex(
        string $table,
        string $name,
        array $columns
    ): bool {
        foreach (Schema::getIndexes($table) as $index) {
            if (strcasecmp($index['name'], $name) === 0) {
                if (! $this->sameColumns($index['columns'], $columns)) {
                    throw new \RuntimeException(
                        "El índice {$name} tiene columnas inesperadas."
                    );
                }

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
