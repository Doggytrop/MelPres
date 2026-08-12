<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\Restructuring;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HardenRestructuringNewLoanRelationTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    private const SIMPLE_FK = 'restructurings_new_loan_id_foreign';
    private const TENANT_FK = 'restruct_new_loan_tenant_fk';

    protected function runDatabaseMigrations(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('This test requires MySQL foreign key semantics.');
        }

        if (! str_contains(strtolower((string) DB::connection()->getDatabaseName()), 'test')) {
            $this->fail('Refusing to alter a MySQL database whose name does not contain test.');
        }

        $this->baseRunDatabaseMigrations();
    }

    public function test_final_constraints_have_the_expected_definitions(): void
    {
        $simple = $this->foreignKey(self::SIMPLE_FK);
        $tenant = $this->foreignKey(self::TENANT_FK);

        $this->assertSame(['new_loan_id'], $simple['columns']);
        $this->assertSame('loans', $simple['foreign_table']);
        $this->assertSame(['id'], $simple['foreign_columns']);
        $this->assertContains(strtoupper($simple['on_update']), ['RESTRICT', 'NO ACTION']);
        $this->assertContains(strtoupper($simple['on_delete']), ['RESTRICT', 'NO ACTION']);

        $this->assertSame(['company_id', 'new_loan_id'], $tenant['columns']);
        $this->assertSame('loans', $tenant['foreign_table']);
        $this->assertSame(['company_id', 'id'], $tenant['foreign_columns']);
        $this->assertContains(strtoupper($tenant['on_update']), ['RESTRICT', 'NO ACTION']);
        $this->assertContains(strtoupper($tenant['on_delete']), ['RESTRICT', 'NO ACTION']);

        $this->assertTrue($this->hasExactIndex(
            'restruct_company_new_loan_idx',
            ['company_id', 'new_loan_id']
        ));
        $this->assertNotNull($this->findForeignKey('restructurings_original_loan_id_foreign'));
        $this->assertNotNull($this->findForeignKey('restruct_original_loan_tenant_fk'));
        $this->assertNotNull($this->findForeignKey('restructurings_recorded_by_foreign'));
        $this->assertNotNull($this->findForeignKey('restruct_recorded_by_tenant_fk'));
    }

    public function test_valid_and_null_references_are_accepted_but_cross_company_is_rejected(): void
    {
        $tenantA = $this->createTenantData('a');
        $tenantB = $this->createTenantData('b');

        $validId = $this->insertRestructuring($tenantA, $tenantA['new_loan_id']);
        $nullId = $this->insertRestructuring($tenantA, null);

        $this->assertDatabaseHas('restructurings', [
            'id' => $validId,
            'company_id' => $tenantA['company_id'],
            'new_loan_id' => $tenantA['new_loan_id'],
        ]);
        $this->assertDatabaseHas('restructurings', [
            'id' => $nullId,
            'new_loan_id' => null,
        ]);

        try {
            $this->insertRestructuring($tenantA, $tenantB['new_loan_id']);
            $this->fail('A cross-company new loan reference was accepted.');
        } catch (QueryException) {
            $this->assertDatabaseMissing('restructurings', [
                'company_id' => $tenantA['company_id'],
                'new_loan_id' => $tenantB['new_loan_id'],
            ]);
        }
    }

    public function test_soft_deleted_new_loan_remains_traceable_and_hard_delete_is_restricted(): void
    {
        $tenant = $this->createTenantData('soft');
        $restructuringId = $this->insertRestructuring($tenant, $tenant['new_loan_id']);

        Loan::query()->findOrFail($tenant['new_loan_id'])->delete();

        $restructuring = Restructuring::query()->findOrFail($restructuringId);
        $newLoan = $restructuring->newLoan;

        $this->assertNotNull($newLoan);
        $this->assertTrue($newLoan->trashed());
        $this->assertSame($tenant['new_loan_id'], $newLoan->getKey());
        $this->assertSame($tenant['company_id'], $newLoan->company_id);
        $this->assertSame(
            $tenant['new_loan_id'],
            DB::table('restructurings')->where('id', $restructuringId)->value('new_loan_id')
        );
        $this->assertSame(
            $tenant['original_loan_id'],
            DB::table('restructurings')->where('id', $restructuringId)->value('original_loan_id')
        );

        $this->expectException(QueryException::class);
        DB::table('loans')->where('id', $tenant['new_loan_id'])->delete();
    }

    public function test_up_is_idempotent(): void
    {
        $migration = $this->migration();

        $migration->up();
        $migration->up();

        $this->assertNotNull($this->findForeignKey(self::SIMPLE_FK));
        $this->assertNotNull($this->findForeignKey(self::TENANT_FK));
        $this->assertSame(1, $this->foreignKeyCount(self::TENANT_FK));
    }

    public function test_down_restores_set_null_is_idempotent_and_up_restores_final_state(): void
    {
        $migration = $this->migration();

        try {
            $migration->down();
            $migration->down();

            $this->assertNull($this->findForeignKey(self::TENANT_FK));

            $simple = $this->foreignKey(self::SIMPLE_FK);
            $this->assertSame('SET NULL', strtoupper($simple['on_delete']));
            $this->assertContains(strtoupper($simple['on_update']), ['NO ACTION', 'RESTRICT']);
            $this->assertTrue($this->hasExactIndex(
                'restruct_company_new_loan_idx',
                ['company_id', 'new_loan_id']
            ));

            $tenant = $this->createTenantData('rollback');
            $restructuringId = $this->insertRestructuring($tenant, $tenant['new_loan_id']);

            DB::table('loans')->where('id', $tenant['new_loan_id'])->delete();

            $this->assertNull(
                DB::table('restructurings')->where('id', $restructuringId)->value('new_loan_id')
            );
        } finally {
            $migration->up();
        }

        $this->assertNotNull($this->findForeignKey(self::SIMPLE_FK));
        $this->assertNotNull($this->findForeignKey(self::TENANT_FK));
    }

    private function createTenantData(string $suffix): array
    {
        $now = now();
        $companyId = DB::table('companies')->insertGetId([
            'name' => "Company {$suffix}",
            'slug' => "company-{$suffix}",
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $customerId = DB::table('customers')->insertGetId([
            'company_id' => $companyId,
            'first_name' => 'Customer',
            'last_name' => $suffix,
            'status' => 'active',
            'score' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $userId = DB::table('users')->insertGetId([
            'company_id' => $companyId,
            'name' => "Recorder {$suffix}",
            'email' => "recorder-{$suffix}@example.test",
            'password' => bcrypt('password'),
            'role' => 'admin',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'company_id' => $companyId,
            'user_id' => $userId,
            'original_loan_id' => $this->insertLoan($companyId, $customerId, $now),
            'new_loan_id' => $this->insertLoan($companyId, $customerId, $now),
        ];
    }

    private function insertLoan(int $companyId, int $customerId, mixed $now): int
    {
        return DB::table('loans')->insertGetId([
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'type' => 'interest',
            'payment_frequency' => 'monthly',
            'original_amount' => 1000,
            'remaining_balance' => 1000,
            'interest_rate' => 10,
            'start_date' => now()->toDateString(),
            'status' => 'active',
            'restructured' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function insertRestructuring(array $tenant, ?int $newLoanId): int
    {
        return DB::table('restructurings')->insertGetId([
            'company_id' => $tenant['company_id'],
            'original_loan_id' => $tenant['original_loan_id'],
            'new_loan_id' => $newLoanId,
            'recorded_by' => $tenant['user_id'],
            'type' => 'extension',
            'balance_at_restructuring' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function migration(): object
    {
        return require database_path(
            'migrations/2026_07_27_000010_harden_restructuring_new_loan_relation.php'
        );
    }

    private function foreignKey(string $name): array
    {
        $foreignKey = $this->findForeignKey($name);
        $this->assertNotNull($foreignKey, "Foreign key {$name} was not found.");

        return $foreignKey;
    }

    private function findForeignKey(string $name): ?array
    {
        foreach (Schema::getForeignKeys('restructurings') as $foreignKey) {
            if (strcasecmp($foreignKey['name'], $name) === 0) {
                return $foreignKey;
            }
        }

        return null;
    }

    private function foreignKeyCount(string $name): int
    {
        return count(array_filter(
            Schema::getForeignKeys('restructurings'),
            fn (array $foreignKey): bool => strcasecmp($foreignKey['name'], $name) === 0
        ));
    }

    private function hasExactIndex(string $name, array $columns): bool
    {
        foreach (Schema::getIndexes('restructurings') as $index) {
            if (strcasecmp($index['name'], $name) === 0
                && $index['columns'] === $columns) {
                return true;
            }
        }

        return false;
    }
}
