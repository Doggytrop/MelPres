<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentsRecordedByTenantForeignKeyTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    private const CONSTRAINT = 'payments_company_recorded_tenant_fk';

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
        $this->assertSame(['company_id', 'recorded_by'], $foreignKey['columns']);
        $this->assertSame('users', $foreignKey['foreign_table']);
        $this->assertSame(['company_id', 'id'], $foreignKey['foreign_columns']);
        $this->assertContains(strtolower($foreignKey['on_update']), ['restrict', 'no action']);
        $this->assertSame('restrict', strtolower($foreignKey['on_delete']));
    }

    public function test_valid_payment_recorded_by_user_from_same_company_is_accepted(): void
    {
        [$companyId, $loanId, $userId] = $this->tenantLoan('Empresa Uno', 'empresa-uno');

        $this->insertPayment($companyId, $loanId, $userId);

        $this->assertDatabaseHas('payments', [
            'company_id' => $companyId,
            'loan_id' => $loanId,
            'recorded_by' => $userId,
        ]);
    }

    public function test_payment_recorded_by_user_from_another_company_is_rejected(): void
    {
        [$companyId, $loanId] = $this->tenantLoan('Empresa Uno', 'empresa-uno');
        [, , $otherUserId] = $this->tenantLoan('Empresa Dos', 'empresa-dos');

        $this->expectException(QueryException::class);

        $this->insertPayment($companyId, $loanId, $otherUserId);
    }

    public function test_referenced_user_cannot_be_deleted(): void
    {
        [$companyId, $loanId, $userId] = $this->tenantLoan('Empresa Uno', 'empresa-uno');
        $this->insertPayment($companyId, $loanId, $userId);

        $this->expectException(QueryException::class);

        DB::table('users')->where('id', $userId)->delete();
    }

    public function test_rollback_removes_only_recorded_by_composite_foreign_key(): void
    {
        $migration = require database_path(
            'migrations/2026_07_21_000014_add_payments_recorded_by_tenant_foreign_key.php'
        );

        $migration->down();

        $this->assertNull($this->foreignKey(self::CONSTRAINT));
        $this->assertNotNull($this->foreignKey('payments_recorded_by_foreign'));
        $this->assertNotNull($this->foreignKey('payments_company_loan_tenant_fk'));
        $this->assertNotNull(
            $this->namedIndex('payments', 'payments_company_recorded_idx')
        );
        $this->assertNotNull(
            $this->namedIndex('users', 'users_company_id_id_unique')
        );

        $migration->up();

        $this->assertNotNull($this->foreignKey(self::CONSTRAINT));
        $this->assertNotNull($this->foreignKey('payments_recorded_by_foreign'));
    }

    private function tenantLoan(string $companyName, string $slug): array
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

        $userId = DB::table('users')->insertGetId([
            'company_id' => $companyId,
            'name' => 'Cobrador '.$companyName,
            'email' => $slug.'@example.com',
            'password' => bcrypt('password'),
            'role' => 'collector',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $loanId = DB::table('loans')->insertGetId([
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

        return [$companyId, $loanId, $userId];
    }

    private function insertPayment(int $companyId, int $loanId, int $userId): void
    {
        DB::table('payments')->insert([
            'company_id' => $companyId,
            'loan_id' => $loanId,
            'amount_paid' => 100,
            'payment_type' => 'capital',
            'payment_date' => now()->toDateString(),
            'recorded_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function foreignKey(string $name): ?array
    {
        return collect(Schema::getForeignKeys('payments'))
            ->first(fn (array $foreignKey) => strcasecmp($foreignKey['name'], $name) === 0);
    }

    private function namedIndex(string $table, string $name): ?array
    {
        return collect(Schema::getIndexes($table))
            ->first(fn (array $index) => strcasecmp($index['name'], $name) === 0);
    }
}
