<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    private const INDEXES = [
        'customers' => [
            ['name' => 'customers_company_status_idx', 'columns' => ['company_id', 'status']],
        ],
        'loans' => [
            ['name' => 'loans_company_customer_idx', 'columns' => ['company_id', 'customer_id']],
            ['name' => 'loans_company_status_idx', 'columns' => ['company_id', 'status']],
            ['name' => 'loans_company_next_payment_idx', 'columns' => ['company_id', 'next_payment_date']],
        ],
        'payments' => [
            ['name' => 'payments_company_loan_idx', 'columns' => ['company_id', 'loan_id']],
            ['name' => 'payments_company_recorded_idx', 'columns' => ['company_id', 'recorded_by']],
            ['name' => 'payments_company_date_idx', 'columns' => ['company_id', 'payment_date']],
        ],
        'customer_documents' => [
            [
                'name' => 'cust_docs_company_customer_type_created_idx',
                'columns' => ['company_id', 'customer_id', 'type', 'created_at'],
            ],
        ],
        'activity_logs' => [
            ['name' => 'activity_logs_company_created_idx', 'columns' => ['company_id', 'created_at']],
            ['name' => 'activity_logs_company_user_idx', 'columns' => ['company_id', 'user_id']],
            [
                'name' => 'activity_logs_company_model_idx',
                'columns' => ['company_id', 'model_type', 'model_id'],
            ],
        ],
        'restructurings' => [
            [
                'name' => 'restruct_company_original_loan_idx',
                'columns' => ['company_id', 'original_loan_id'],
            ],
            [
                'name' => 'restruct_company_new_loan_idx',
                'columns' => ['company_id', 'new_loan_id'],
            ],
            [
                'name' => 'restruct_company_recorded_idx',
                'columns' => ['company_id', 'recorded_by'],
            ],
        ],
    ];

    private const COMPANY_SUPPORT_INDEXES = [
        'customers' => 'customers_company_id_foreign',
        'loans' => 'loans_company_id_foreign',
        'payments' => 'payments_company_id_foreign',
        'customer_documents' => 'customer_documents_company_id_foreign',
        'activity_logs' => 'activity_logs_company_id_foreign',
        'restructurings' => 'restructurings_company_id_foreign',
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            foreach ($indexes as $index) {
                if ($this->hasEquivalentIndex($table, $index['columns'])) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($index) {
                    $blueprint->index($index['columns'], $index['name']);
                });
            }
        }

        $this->dropRedundantCompanySupportIndexes();
    }

    public function down(): void
    {
        $this->restoreCompanySupportIndexes();

        foreach (array_reverse(self::INDEXES, true) as $table => $indexes) {
            foreach (array_reverse($indexes) as $index) {
                if (! $this->hasNamedIndex($table, $index['name'])) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($index) {
                    $blueprint->dropIndex($index['name']);
                });
            }
        }
    }

    private function restoreCompanySupportIndexes(): void
    {
        foreach (self::COMPANY_SUPPORT_INDEXES as $table => $name) {
            if ($this->hasEquivalentIndex($table, ['company_id'])) {
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

    private function hasEquivalentIndex(string $table, array $columns): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($this->sameColumns($index['columns'], $columns)) {
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
