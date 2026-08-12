<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    private const CONSTRAINT = 'cust_docs_company_customer_tenant_fk';

    public function up(): void
    {
        if ($this->hasForeignKey(self::CONSTRAINT)) {
            return;
        }

        if (! Schema::hasColumns('customer_documents', ['company_id', 'customer_id'])
            || ! Schema::hasColumns('customers', ['company_id', 'id'])) {
            throw new \RuntimeException(
                'No se puede crear cust_docs_company_customer_tenant_fk: faltan columnas requeridas.'
            );
        }

        if (! $this->hasIndexPrefix(
            'customer_documents',
            ['company_id', 'customer_id']
        )) {
            throw new \RuntimeException(
                'No se puede crear cust_docs_company_customer_tenant_fk: falta el índice hijo.'
            );
        }

        if (! $this->hasExactIndex('customers', ['company_id', 'id'], true)) {
            throw new \RuntimeException(
                'No se puede crear cust_docs_company_customer_tenant_fk: falta el UNIQUE padre.'
            );
        }

        Schema::table('customer_documents', function (Blueprint $table) {
            $table->foreign(
                ['company_id', 'customer_id'],
                self::CONSTRAINT
            )
                ->references(['company_id', 'id'])
                ->on('customers')
                ->restrictOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! $this->hasForeignKey(self::CONSTRAINT)) {
            return;
        }

        Schema::table('customer_documents', function (Blueprint $table) {
            $table->dropForeign(self::CONSTRAINT);
        });
    }

    private function hasForeignKey(string $name): bool
    {
        foreach (Schema::getForeignKeys('customer_documents') as $foreignKey) {
            if (strcasecmp($foreignKey['name'], $name) === 0) {
                return true;
            }
        }

        return false;
    }

    private function hasIndexPrefix(string $table, array $prefix): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            $columns = array_map('strtolower', $index['columns']);
            $required = array_map('strtolower', $prefix);

            if (array_slice($columns, 0, count($required)) === $required) {
                return true;
            }
        }

        return false;
    }

    private function hasExactIndex(string $table, array $columns, bool $unique): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($this->sameColumns($index['columns'], $columns)
                && (bool) $index['unique'] === $unique) {
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
