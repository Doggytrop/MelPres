<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    private const CONSTRAINT = 'payments_company_loan_tenant_fk';

    public function up(): void
    {
        if ($this->hasForeignKey(self::CONSTRAINT)) {
            return;
        }

        if (! Schema::hasColumns('payments', ['company_id', 'loan_id'])
            || ! Schema::hasColumns('loans', ['company_id', 'id'])) {
            throw new \RuntimeException(
                'No se puede crear payments_company_loan_tenant_fk: faltan columnas requeridas.'
            );
        }

        if (! $this->hasIndex('payments', ['company_id', 'loan_id'])) {
            throw new \RuntimeException(
                'No se puede crear payments_company_loan_tenant_fk: falta el índice hijo.'
            );
        }

        if (! $this->hasIndex('loans', ['company_id', 'id'], true)) {
            throw new \RuntimeException(
                'No se puede crear payments_company_loan_tenant_fk: falta el UNIQUE padre.'
            );
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign(
                ['company_id', 'loan_id'],
                self::CONSTRAINT
            )
                ->references(['company_id', 'id'])
                ->on('loans')
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (! $this->hasForeignKey(self::CONSTRAINT)) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(self::CONSTRAINT);
        });
    }

    private function hasForeignKey(string $name): bool
    {
        foreach (Schema::getForeignKeys('payments') as $foreignKey) {
            if (strcasecmp($foreignKey['name'], $name) === 0) {
                return true;
            }
        }

        return false;
    }

    private function hasIndex(string $table, array $columns, bool $unique = false): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($this->sameColumns($index['columns'], $columns)
                && (! $unique || (bool) $index['unique'])) {
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
