<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantIndexMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const PARENT_INDEXES = [
        'customers' => ['customers_company_id_id_unique', ['company_id', 'id']],
        'loans' => ['loans_company_id_id_unique', ['company_id', 'id']],
        'users' => ['users_company_id_id_unique', ['company_id', 'id']],
    ];

    private const RELATION_INDEXES = [
        'customers' => [
            ['customers_company_status_idx', ['company_id', 'status']],
        ],
        'loans' => [
            ['loans_company_customer_idx', ['company_id', 'customer_id']],
            ['loans_company_status_idx', ['company_id', 'status']],
            ['loans_company_next_payment_idx', ['company_id', 'next_payment_date']],
        ],
        'payments' => [
            ['payments_company_loan_idx', ['company_id', 'loan_id']],
            ['payments_company_recorded_idx', ['company_id', 'recorded_by']],
            ['payments_company_date_idx', ['company_id', 'payment_date']],
        ],
        'customer_documents' => [
            [
                'cust_docs_company_customer_type_created_idx',
                ['company_id', 'customer_id', 'type', 'created_at'],
            ],
        ],
        'activity_logs' => [
            ['activity_logs_company_created_idx', ['company_id', 'created_at']],
            ['activity_logs_company_user_idx', ['company_id', 'user_id']],
            ['activity_logs_company_model_idx', ['company_id', 'model_type', 'model_id']],
        ],
        'restructurings' => [
            ['restruct_company_original_loan_idx', ['company_id', 'original_loan_id']],
            ['restruct_company_new_loan_idx', ['company_id', 'new_loan_id']],
            ['restruct_company_recorded_idx', ['company_id', 'recorded_by']],
        ],
    ];

    public function test_phase_b_parent_unique_indexes_exist(): void
    {
        foreach (self::PARENT_INDEXES as $table => [$name, $columns]) {
            $this->assertIndex($table, $name, $columns, true);
        }
    }

    public function test_phase_b_relation_indexes_exist(): void
    {
        foreach (self::RELATION_INDEXES as $table => $indexes) {
            foreach ($indexes as [$name, $columns]) {
                $this->assertIndex($table, $name, $columns, false);
            }
        }
    }

    private function assertIndex(
        string $table,
        string $name,
        array $columns,
        bool $unique
    ): void {
        foreach (Schema::getIndexes($table) as $index) {
            if (strcasecmp($index['name'], $name) === 0) {
                $this->assertSame($columns, $index['columns']);
                $this->assertSame($unique, (bool) $index['unique']);

                return;
            }
        }

        $this->fail("No existe el índice {$name} en {$table}.");
    }

}
