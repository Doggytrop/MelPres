<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LoansCustomerTenantForeignKeyTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    private const CONSTRAINT = 'loans_company_customer_tenant_fk';

    public function runDatabaseMigrations()
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped(
                'La foreign key compuesta requiere validación definitiva sobre MySQL.'
            );
        }

        $database = strtolower((string) DB::getDatabaseName());

        if (! str_contains($database, 'test')) {
            $this->fail('Las pruebas DDL solo pueden ejecutarse en una base MySQL de pruebas.');
        }

        $this->baseRunDatabaseMigrations();
    }

    public function test_composite_foreign_key_has_the_expected_definition(): void
    {
        $foreignKey = $this->foreignKey(self::CONSTRAINT);

        $this->assertNotNull($foreignKey);
        $this->assertSame(['company_id', 'customer_id'], $foreignKey['columns']);
        $this->assertSame('customers', $foreignKey['foreign_table']);
        $this->assertSame(['company_id', 'id'], $foreignKey['foreign_columns']);
        $this->assertContains(strtolower($foreignKey['on_update']), ['restrict', 'no action']);
        $this->assertSame('restrict', strtolower($foreignKey['on_delete']));
    }

    public function test_valid_loan_from_the_same_company_is_accepted(): void
    {
        [$companyId, $customerId] = $this->tenantCustomer('Empresa Uno', 'empresa-uno');

        $this->insertLoan($companyId, $customerId);

        $this->assertDatabaseHas('loans', [
            'company_id' => $companyId,
            'customer_id' => $customerId,
        ]);
    }

    public function test_loan_from_a_different_company_is_rejected(): void
    {
        [, $customerId] = $this->tenantCustomer('Empresa Uno', 'empresa-uno');
        [$otherCompanyId] = $this->tenantCustomer('Empresa Dos', 'empresa-dos');

        $this->expectException(QueryException::class);

        $this->insertLoan($otherCompanyId, $customerId);
    }

    public function test_referenced_customer_cannot_be_deleted(): void
    {
        [$companyId, $customerId] = $this->tenantCustomer('Empresa Uno', 'empresa-uno');
        $this->insertLoan($companyId, $customerId);

        $this->expectException(QueryException::class);

        DB::table('customers')->where('id', $customerId)->delete();
    }

    public function test_rollback_removes_only_composite_foreign_key(): void
    {
        $migration = require database_path(
            'migrations/2026_07_21_000011_add_loans_customer_tenant_foreign_key.php'
        );

        $migration->down();

        $this->assertNull($this->foreignKey(self::CONSTRAINT));
        $this->assertNotNull($this->foreignKey('loans_customer_id_foreign'));
        $this->assertNotNull($this->namedIndex('loans', 'loans_company_customer_idx'));
        $this->assertNotNull(
            $this->namedIndex('customers', 'customers_company_id_id_unique')
        );

        $migration->up();

        $this->assertNotNull($this->foreignKey(self::CONSTRAINT));
        $this->assertNotNull($this->foreignKey('loans_customer_id_foreign'));
    }

    private function tenantCustomer(string $companyName, string $slug): array
    {
        $companyId = DB::table('companies')->insertGetId([
            'name' => $companyName,
            'slug' => $slug,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $customerId = DB::table('customers')->insertGetId([
            'company_id' => $companyId,
            'first_name' => 'Cliente',
            'last_name' => $companyName,
            'status' => 'active',
            'score' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$companyId, $customerId];
    }

    private function insertLoan(int $companyId, int $customerId): void
    {
        DB::table('loans')->insert([
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'type' => 'interest',
            'payment_frequency' => 'monthly',
            'original_amount' => 1000,
            'remaining_balance' => 1000,
            'interest_rate' => 10,
            'start_date' => now()->toDateString(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function foreignKey(string $name): ?array
    {
        return collect(Schema::getForeignKeys('loans'))
            ->first(fn (array $foreignKey) => strcasecmp($foreignKey['name'], $name) === 0);
    }

    private function namedIndex(string $table, string $name): ?array
    {
        return collect(Schema::getIndexes($table))
            ->first(fn (array $index) => strcasecmp($index['name'], $name) === 0);
    }
}
