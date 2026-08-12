<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class HardenPaymentsCompanyIdTest extends TestCase
{
    private string $companiesTable = 'hp_companies';

    private string $usersTable = 'hp_users';

    private string $loansTable = 'hp_loans';

    private string $paymentsTable = 'hp_payments';

    private string $companyForeignKey = 'hp_payments_company_id_foreign';

    private string $loanTenantForeignKey = 'hp_payments_loan_tenant_fk';

    private string $recordedTenantForeignKey = 'hp_payments_recorded_tenant_fk';

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('This test requires MySQL.');
        }

        if (! str_contains(strtolower((string) DB::connection()->getDatabaseName()), 'test')) {
            $this->fail(
                'Refusing to alter a MySQL database whose name does not contain test.'
            );
        }

        $this->createInitialSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists($this->paymentsTable);
        Schema::dropIfExists($this->loansTable);
        Schema::dropIfExists($this->usersTable);
        Schema::dropIfExists($this->companiesTable);

        parent::tearDown();
    }

    public function test_up_is_idempotent_and_preserves_data_columns_indexes_and_tenant_fks(): void
    {
        $rows = $this->rows();
        $columns = $this->otherColumns();
        $indexes = Schema::getIndexes($this->paymentsTable);
        $tenantForeignKeys = $this->tenantForeignKeys();

        $migration = $this->migration();
        $migration->up();
        $migration->up();

        $column = $this->column('company_id');
        $this->assertSame('bigint unsigned', strtolower($column['type']));
        $this->assertFalse($column['nullable']);
        $this->assertStringNotContainsString(
            'DEFAULT',
            strtoupper($this->companyIdDefinition())
        );
        $this->assertCompanyForeignKeyRules('RESTRICT');
        $this->assertSame($rows, $this->rows());
        $this->assertSame($columns, $this->otherColumns());
        $this->assertSame($indexes, Schema::getIndexes($this->paymentsTable));
        $this->assertSame($tenantForeignKeys, $this->tenantForeignKeys());
        $this->assertFalse($this->hasCompanyIdUnique());
    }

    public function test_valid_payment_is_accepted_and_invalid_tenant_values_are_rejected(): void
    {
        $this->migration()->up();

        DB::table($this->paymentsTable)->insert([
            'company_id' => 2,
            'loan_id' => 2,
            'recorded_by' => 2,
            'payment_date' => '2026-07-28',
            'marker' => 'valid',
        ]);

        $this->assertQueryFails(fn () => $this->insertPayment(null, 1, 1, 'null'));
        $this->assertQueryFails(
            fn () => $this->insertPayment(999999, 1, 1, 'orphan')
        );
        $this->assertQueryFails(
            fn () => $this->insertPayment(2, 1, 2, 'cross-loan')
        );
        $this->assertQueryFails(
            fn () => $this->insertPayment(2, 2, 1, 'cross-user')
        );
    }

    public function test_company_with_payments_cannot_be_physically_deleted(): void
    {
        $this->migration()->up();

        $this->assertQueryFails(
            fn () => DB::table($this->companiesTable)->where('id', 1)->delete()
        );
    }

    public function test_down_restores_nullable_set_null_and_is_idempotent(): void
    {
        $migration = $this->migration();
        $migration->up();
        $migration->down();
        $migration->down();

        $this->assertTrue($this->column('company_id')['nullable']);
        $this->assertMatchesRegularExpression(
            '/DEFAULT\s+NULL/i',
            $this->companyIdDefinition()
        );
        $this->assertCompanyForeignKeyRules('SET NULL');
        $this->assertFalse($this->hasCompanyIdUnique());

        DB::table($this->companiesTable)->where('id', 1)->delete();

        $this->assertNull(
            DB::table($this->paymentsTable)->where('id', 1)->value('company_id')
        );
    }

    public function test_up_refuses_existing_null_company_ids(): void
    {
        $this->insertPayment(null, 1, 1, 'legacy-null');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('NULL values');

        $this->migration()->up();
    }

    public function test_up_refuses_cross_company_loan_data(): void
    {
        DB::statement(
            "ALTER TABLE `{$this->paymentsTable}` "
            ."DROP FOREIGN KEY `{$this->loanTenantForeignKey}`"
        );
        $this->insertPayment(2, 1, 2, 'legacy-cross-loan');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('payment/loan');

        $this->migration()->up();
    }

    public function test_up_refuses_cross_company_user_data(): void
    {
        DB::statement(
            "ALTER TABLE `{$this->paymentsTable}` "
            ."DROP FOREIGN KEY `{$this->recordedTenantForeignKey}`"
        );
        $this->insertPayment(2, 2, 1, 'legacy-cross-user');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('recording user');

        $this->migration()->up();
    }

    public function test_up_completes_safe_partial_state_without_simple_fk(): void
    {
        $this->dropCompanyForeignKey();

        $this->migration()->up();

        $this->assertFalse($this->column('company_id')['nullable']);
        $this->assertCompanyForeignKeyRules('RESTRICT');
    }

    public function test_up_completes_safe_partial_state_after_column_change(): void
    {
        $this->dropCompanyForeignKey();
        DB::statement(
            "ALTER TABLE `{$this->paymentsTable}` "
            .'MODIFY COLUMN `company_id` BIGINT UNSIGNED NOT NULL'
        );

        $this->migration()->up();

        $this->assertFalse($this->column('company_id')['nullable']);
        $this->assertCompanyForeignKeyRules('RESTRICT');
    }

    public function test_up_refuses_unexpected_simple_fk_rules(): void
    {
        $this->dropCompanyForeignKey();
        DB::statement(
            "ALTER TABLE `{$this->paymentsTable}` "
            ."ADD CONSTRAINT `{$this->companyForeignKey}` FOREIGN KEY (`company_id`) "
            ."REFERENCES `{$this->companiesTable}` (`id`) "
            .'ON UPDATE RESTRICT ON DELETE CASCADE'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unexpected update/delete rules');

        $this->migration()->up();
    }

    public function test_up_refuses_unexpected_composite_fk_definition(): void
    {
        DB::statement(
            "ALTER TABLE `{$this->paymentsTable}` "
            ."DROP FOREIGN KEY `{$this->recordedTenantForeignKey}`"
        );
        DB::statement(
            "ALTER TABLE `{$this->paymentsTable}` "
            ."ADD CONSTRAINT `{$this->recordedTenantForeignKey}` "
            .'FOREIGN KEY (`company_id`, `recorded_by`) '
            ."REFERENCES `{$this->usersTable}` (`company_id`, `id`) "
            .'ON UPDATE RESTRICT ON DELETE CASCADE'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unexpected definition');

        $this->migration()->up();
    }

    public function test_down_completes_safe_partial_state_without_changing_data_or_indexes(): void
    {
        $migration = $this->migration();
        $migration->up();
        $rows = $this->rows();
        $indexes = Schema::getIndexes($this->paymentsTable);
        $this->dropCompanyForeignKey();

        $migration->down();

        $this->assertTrue($this->column('company_id')['nullable']);
        $this->assertCompanyForeignKeyRules('SET NULL');
        $this->assertSame($rows, $this->rows());
        $this->assertSame($indexes, Schema::getIndexes($this->paymentsTable));
    }

    private function createInitialSchema(): void
    {
        Schema::dropIfExists($this->paymentsTable);
        Schema::dropIfExists($this->loansTable);
        Schema::dropIfExists($this->usersTable);
        Schema::dropIfExists($this->companiesTable);

        Schema::create($this->companiesTable, function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        Schema::create($this->usersTable, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
            $table->unique(['company_id', 'id'], 'hp_users_company_id_unique');
        });

        Schema::create($this->loansTable, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->unique(['company_id', 'id'], 'hp_loans_company_id_unique');
        });

        Schema::create($this->paymentsTable, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->default(null);
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('recorded_by');
            $table->date('payment_date');
            $table->string('marker')->nullable();
            $table->index('loan_id', 'hp_payments_loan_id_idx');
            $table->index('recorded_by', 'hp_payments_recorded_by_idx');
            $table->index(
                ['company_id', 'loan_id'],
                'hp_payments_company_loan_idx'
            );
            $table->index(
                ['company_id', 'recorded_by'],
                'hp_payments_company_recorded_idx'
            );
            $table->index(
                ['company_id', 'payment_date'],
                'hp_payments_company_date_idx'
            );
            $table->foreign('company_id', $this->companyForeignKey)
                ->references('id')->on($this->companiesTable)
                ->noActionOnUpdate()
                ->nullOnDelete();
            $table->foreign(
                ['company_id', 'loan_id'],
                $this->loanTenantForeignKey
            )
                ->references(['company_id', 'id'])
                ->on($this->loansTable)
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->foreign(
                ['company_id', 'recorded_by'],
                $this->recordedTenantForeignKey
            )
                ->references(['company_id', 'id'])
                ->on($this->usersTable)
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });

        DB::table($this->companiesTable)->insert([
            ['id' => 1, 'name' => 'Company One'],
            ['id' => 2, 'name' => 'Company Two'],
        ]);
        DB::table($this->usersTable)->insert([
            ['id' => 1, 'company_id' => 1, 'name' => 'User One'],
            ['id' => 2, 'company_id' => 2, 'name' => 'User Two'],
        ]);
        DB::table($this->loansTable)->insert([
            ['id' => 1, 'company_id' => 1, 'name' => 'Loan One'],
            ['id' => 2, 'company_id' => 2, 'name' => 'Loan Two'],
        ]);
        $this->insertPayment(1, 1, 1, 'existing');
    }

    private function migration(): object
    {
        $migration = require database_path(
            'migrations/2026_07_27_000070_harden_payments_company_id.php'
        );

        foreach ([
            'paymentsTable' => $this->paymentsTable,
            'companiesTable' => $this->companiesTable,
            'loansTable' => $this->loansTable,
            'usersTable' => $this->usersTable,
            'loanTenantForeignKey' => $this->loanTenantForeignKey,
            'recordedTenantForeignKey' => $this->recordedTenantForeignKey,
        ] as $property => $value) {
            $reflection = new \ReflectionProperty($migration, $property);
            $reflection->setValue($migration, $value);
        }

        return $migration;
    }

    private function insertPayment(
        ?int $companyId,
        int $loanId,
        int $recordedBy,
        string $marker
    ): void {
        DB::table($this->paymentsTable)->insert([
            'company_id' => $companyId,
            'loan_id' => $loanId,
            'recorded_by' => $recordedBy,
            'payment_date' => '2026-07-28',
            'marker' => $marker,
        ]);
    }

    private function dropCompanyForeignKey(): void
    {
        if ($this->findForeignKey($this->companyForeignKey) === null) {
            return;
        }

        DB::statement(
            "ALTER TABLE `{$this->paymentsTable}` "
            ."DROP FOREIGN KEY `{$this->companyForeignKey}`"
        );
    }

    private function tenantForeignKeys(): array
    {
        return [
            $this->loanTenantForeignKey => $this->foreignKey(
                $this->loanTenantForeignKey
            ),
            $this->recordedTenantForeignKey => $this->foreignKey(
                $this->recordedTenantForeignKey
            ),
        ];
    }

    private function assertCompanyForeignKeyRules(string $deleteRule): void
    {
        $foreignKey = $this->foreignKey($this->companyForeignKey);

        $this->assertSame(['company_id'], $foreignKey['columns']);
        $this->assertSame($this->companiesTable, $foreignKey['foreign_table']);
        $this->assertSame(['id'], $foreignKey['foreign_columns']);
        $this->assertContains(
            strtoupper($foreignKey['on_update']),
            ['RESTRICT', 'NO ACTION']
        );

        if ($deleteRule === 'SET NULL') {
            $this->assertSame('SET NULL', strtoupper($foreignKey['on_delete']));
        } else {
            $this->assertContains(
                strtoupper($foreignKey['on_delete']),
                ['RESTRICT', 'NO ACTION']
            );
        }
    }

    private function foreignKey(string $name): array
    {
        $foreignKey = $this->findForeignKey($name);
        $this->assertNotNull($foreignKey, "Foreign key {$name} was not found.");

        return $foreignKey;
    }

    private function findForeignKey(string $name): ?array
    {
        foreach (Schema::getForeignKeys($this->paymentsTable) as $foreignKey) {
            if (strcasecmp($foreignKey['name'], $name) === 0) {
                return $foreignKey;
            }
        }

        return null;
    }

    private function hasCompanyIdUnique(): bool
    {
        foreach (Schema::getIndexes($this->paymentsTable) as $index) {
            if ($index['unique']
                && $index['columns'] === ['company_id', 'id']) {
                return true;
            }
        }

        return false;
    }

    private function assertQueryFails(callable $callback): void
    {
        try {
            $callback();
            $this->fail('The database accepted an invalid operation.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }

    private function column(string $name): array
    {
        $column = collect(Schema::getColumns($this->paymentsTable))
            ->firstWhere('name', $name);
        $this->assertIsArray($column);

        return $column;
    }

    private function companyIdDefinition(): string
    {
        $row = (array) DB::selectOne("SHOW CREATE TABLE `{$this->paymentsTable}`");
        $createSql = array_values($row)[1];
        preg_match('/^\s*`company_id`\s+(.+?)(?:,\s*)?$/mi', $createSql, $matches);

        return trim($matches[1] ?? '');
    }

    private function rows(): array
    {
        return DB::table($this->paymentsTable)
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();
    }

    private function otherColumns(): array
    {
        return array_values(array_filter(
            Schema::getColumns($this->paymentsTable),
            fn (array $column): bool => $column['name'] !== 'company_id'
        ));
    }
}
