<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RestructuringsOriginalLoanTenantForeignKeyTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    private const CONSTRAINT = 'restruct_original_loan_tenant_fk';

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
        $this->assertSame(['company_id', 'original_loan_id'], $foreignKey['columns']);
        $this->assertSame('loans', $foreignKey['foreign_table']);
        $this->assertSame(['company_id', 'id'], $foreignKey['foreign_columns']);
        $this->assertContains(strtolower($foreignKey['on_update']), ['restrict', 'no action']);
        $this->assertSame('restrict', strtolower($foreignKey['on_delete']));
    }

    public function test_valid_restructuring_from_the_same_company_is_accepted(): void
    {
        [$companyId, $loanId, $userId] = $this->tenantLoan('Empresa Uno', 'empresa-uno');

        $this->insertRestructuring($companyId, $loanId, $userId);

        $this->assertDatabaseHas('restructurings', [
            'company_id' => $companyId,
            'original_loan_id' => $loanId,
            'recorded_by' => $userId,
        ]);
    }

    public function test_original_loan_from_another_company_is_rejected(): void
    {
        [, $loanId] = $this->tenantLoan('Empresa Uno', 'empresa-uno');
        [$otherCompanyId, , $otherUserId] = $this->tenantLoan(
            'Empresa Dos',
            'empresa-dos'
        );

        $this->expectException(QueryException::class);

        $this->insertRestructuring($otherCompanyId, $loanId, $otherUserId);
    }

    public function test_referenced_original_loan_cannot_be_deleted(): void
    {
        [$companyId, $loanId, $userId] = $this->tenantLoan('Empresa Uno', 'empresa-uno');
        $this->insertRestructuring($companyId, $loanId, $userId);

        $this->expectException(QueryException::class);

        DB::table('loans')->where('id', $loanId)->delete();
    }

    public function test_rollback_removes_only_original_loan_composite_foreign_key(): void
    {
        $migration = require database_path(
            'migrations/2026_07_21_000015_add_restructurings_original_loan_tenant_foreign_key.php'
        );

        $migration->down();

        $this->assertNull($this->foreignKey(self::CONSTRAINT));
        $this->assertNotNull(
            $this->foreignKey('restructurings_original_loan_id_foreign')
        );
        $this->assertNotNull(
            $this->namedIndex('restructurings', 'restruct_company_original_loan_idx')
        );
        $this->assertNotNull(
            $this->namedIndex('loans', 'loans_company_id_id_unique')
        );

        $migration->up();

        $this->assertNotNull($this->foreignKey(self::CONSTRAINT));
        $this->assertNotNull(
            $this->foreignKey('restructurings_original_loan_id_foreign')
        );
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
            'name' => 'Administrador '.$companyName,
            'email' => $slug.'@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
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

    private function insertRestructuring(
        int $companyId,
        int $loanId,
        int $userId
    ): void {
        DB::table('restructurings')->insert([
            'company_id' => $companyId,
            'original_loan_id' => $loanId,
            'new_loan_id' => null,
            'recorded_by' => $userId,
            'type' => 'extension',
            'balance_at_restructuring' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function foreignKey(string $name): ?array
    {
        return collect(Schema::getForeignKeys('restructurings'))
            ->first(fn (array $foreignKey) => strcasecmp($foreignKey['name'], $name) === 0);
    }

    private function namedIndex(string $table, string $name): ?array
    {
        return collect(Schema::getIndexes($table))
            ->first(fn (array $index) => strcasecmp($index['name'], $name) === 0);
    }
}
