<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class HardenLoansCompanyIdTest extends TestCase
{
    private string $companiesTable = 'hl_companies';

    private string $customersTable = 'hl_customers';

    private string $loansTable = 'hl_loans';

    private string $paymentsTable = 'hl_payments';

    private string $restructuringsTable = 'hl_restructurings';

    private string $companyForeignKey = 'hl_loans_company_id_foreign';

    private string $customerForeignKey = 'hl_loans_customer_tenant_fk';

    private string $loanUnique = 'hl_loans_company_id_unique';

    private string $paymentForeignKey = 'hl_payments_loan_tenant_fk';

    private string $originalLoanForeignKey = 'hl_restruct_original_tenant_fk';

    private string $newLoanForeignKey = 'hl_restruct_new_tenant_fk';

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
        Schema::dropIfExists($this->restructuringsTable);
        Schema::dropIfExists($this->loansTable);
        Schema::dropIfExists($this->customersTable);
        Schema::dropIfExists($this->companiesTable);

        parent::tearDown();
    }

    public function test_up_is_idempotent_and_preserves_schema_data_and_foreign_keys(): void
    {
        $rows = $this->rows();
        $indexes = Schema::getIndexes($this->loansTable);
        $customerForeignKey = $this->foreignKey(
            $this->loansTable,
            $this->customerForeignKey
        );
        $dependentForeignKeys = $this->dependentForeignKeys();

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
        $this->assertTrue($this->hasLoanUnique());
        $this->assertSame($rows, $this->rows());
        $this->assertSame($indexes, Schema::getIndexes($this->loansTable));
        $this->assertSame(
            $customerForeignKey,
            $this->foreignKey($this->loansTable, $this->customerForeignKey)
        );
        $this->assertSame($dependentForeignKeys, $this->dependentForeignKeys());
    }

    public function test_valid_loan_is_accepted_and_invalid_tenant_values_are_rejected(): void
    {
        $this->migration()->up();

        DB::table($this->loansTable)->insert([
            'company_id' => 2,
            'customer_id' => 2,
            'marker' => 'valid',
        ]);

        $this->assertQueryFails(fn () => DB::table($this->loansTable)->insert([
            'company_id' => null,
            'customer_id' => 1,
            'marker' => 'null',
        ]));
        $this->assertQueryFails(fn () => DB::table($this->loansTable)->insert([
            'company_id' => 999999,
            'customer_id' => 1,
            'marker' => 'orphan',
        ]));
        $this->assertQueryFails(fn () => DB::table($this->loansTable)->insert([
            'company_id' => 2,
            'customer_id' => 1,
            'marker' => 'cross-company',
        ]));
    }

    public function test_company_with_loans_cannot_be_physically_deleted(): void
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
        $this->assertTrue($this->hasLoanUnique());
    }

    public function test_up_refuses_null_company_ids(): void
    {
        DB::table($this->loansTable)->insert([
            'company_id' => null,
            'customer_id' => 1,
            'marker' => 'legacy-null',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('NULL values');

        $this->migration()->up();
    }

    public function test_up_refuses_cross_company_customer_relationships(): void
    {
        DB::statement(
            "ALTER TABLE `{$this->loansTable}` "
            ."DROP FOREIGN KEY `{$this->customerForeignKey}`"
        );
        DB::table($this->loansTable)->insert([
            'company_id' => 2,
            'customer_id' => 1,
            'marker' => 'legacy-cross',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cross-company');

        $this->migration()->up();
    }

    public function test_up_completes_safe_partial_state_without_simple_foreign_key(): void
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
            "ALTER TABLE `{$this->loansTable}` "
            .'MODIFY COLUMN `company_id` BIGINT UNSIGNED NOT NULL'
        );

        $this->migration()->up();

        $this->assertFalse($this->column('company_id')['nullable']);
        $this->assertCompanyForeignKeyRules('RESTRICT');
    }

    public function test_up_refuses_unexpected_simple_foreign_key_rules(): void
    {
        $this->dropCompanyForeignKey();
        DB::statement(
            "ALTER TABLE `{$this->loansTable}` "
            ."ADD CONSTRAINT `{$this->companyForeignKey}` FOREIGN KEY (`company_id`) "
            ."REFERENCES `{$this->companiesTable}` (`id`) "
            .'ON UPDATE RESTRICT ON DELETE CASCADE'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unexpected update/delete rules');

        $this->migration()->up();
    }

    public function test_up_refuses_duplicate_tenant_unique(): void
    {
        DB::statement(
            "ALTER TABLE `{$this->loansTable}` "
            .'ADD UNIQUE INDEX `hl_loans_duplicate_unique` (`company_id`, `id`)'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('exactly one UNIQUE index');

        $this->migration()->up();
    }

    public function test_up_refuses_changed_dependent_foreign_key(): void
    {
        DB::statement(
            "ALTER TABLE `{$this->paymentsTable}` "
            ."DROP FOREIGN KEY `{$this->paymentForeignKey}`"
        );
        DB::statement(
            "ALTER TABLE `{$this->paymentsTable}` "
            ."ADD CONSTRAINT `{$this->paymentForeignKey}` "
            .'FOREIGN KEY (`company_id`, `loan_id`) '
            ."REFERENCES `{$this->loansTable}` (`company_id`, `id`) "
            .'ON UPDATE RESTRICT ON DELETE CASCADE'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unexpected definition');

        $this->migration()->up();
    }

    public function test_down_completes_safe_partial_state_without_changing_rows(): void
    {
        $migration = $this->migration();
        $migration->up();
        $rows = $this->rows();
        $dependentForeignKeys = $this->dependentForeignKeys();
        $this->dropCompanyForeignKey();

        $migration->down();

        $this->assertTrue($this->column('company_id')['nullable']);
        $this->assertCompanyForeignKeyRules('SET NULL');
        $this->assertSame($rows, $this->rows());
        $this->assertSame($dependentForeignKeys, $this->dependentForeignKeys());
    }

    private function createInitialSchema(): void
    {
        Schema::dropIfExists($this->paymentsTable);
        Schema::dropIfExists($this->restructuringsTable);
        Schema::dropIfExists($this->loansTable);
        Schema::dropIfExists($this->customersTable);
        Schema::dropIfExists($this->companiesTable);

        Schema::create($this->companiesTable, function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        Schema::create($this->customersTable, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->unique(
                ['company_id', 'id'],
                'hl_customers_company_id_unique'
            );
        });

        Schema::create($this->loansTable, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->default(null);
            $table->unsignedBigInteger('customer_id');
            $table->string('marker')->nullable();
            $table->unique(['company_id', 'id'], $this->loanUnique);
            $table->index(
                ['company_id', 'customer_id'],
                'hl_loans_company_customer_idx'
            );
            $table->foreign('company_id', $this->companyForeignKey)
                ->references('id')->on($this->companiesTable)
                ->noActionOnUpdate()
                ->nullOnDelete();
            $table->foreign(
                ['company_id', 'customer_id'],
                $this->customerForeignKey
            )
                ->references(['company_id', 'id'])
                ->on($this->customersTable)
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });

        Schema::create($this->paymentsTable, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('loan_id');
            $table->index(
                ['company_id', 'loan_id'],
                'hl_payments_company_loan_idx'
            );
            $table->foreign(
                ['company_id', 'loan_id'],
                $this->paymentForeignKey
            )
                ->references(['company_id', 'id'])
                ->on($this->loansTable)
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });

        Schema::create($this->restructuringsTable, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('original_loan_id');
            $table->unsignedBigInteger('new_loan_id')->nullable();
            $table->index(
                ['company_id', 'original_loan_id'],
                'hl_restruct_original_idx'
            );
            $table->index(
                ['company_id', 'new_loan_id'],
                'hl_restruct_new_idx'
            );
            $table->foreign(
                ['company_id', 'original_loan_id'],
                $this->originalLoanForeignKey
            )
                ->references(['company_id', 'id'])
                ->on($this->loansTable)
                ->restrictOnUpdate()
                ->restrictOnDelete();
            $table->foreign(
                ['company_id', 'new_loan_id'],
                $this->newLoanForeignKey
            )
                ->references(['company_id', 'id'])
                ->on($this->loansTable)
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });

        DB::table($this->companiesTable)->insert([
            ['id' => 1, 'name' => 'Company One'],
            ['id' => 2, 'name' => 'Company Two'],
        ]);
        DB::table($this->customersTable)->insert([
            ['id' => 1, 'company_id' => 1, 'name' => 'Customer One'],
            ['id' => 2, 'company_id' => 2, 'name' => 'Customer Two'],
        ]);
        DB::table($this->loansTable)->insert([
            'id' => 1,
            'company_id' => 1,
            'customer_id' => 1,
            'marker' => 'existing',
        ]);
        DB::table($this->paymentsTable)->insert([
            'id' => 1,
            'company_id' => 1,
            'loan_id' => 1,
        ]);
        DB::table($this->restructuringsTable)->insert([
            'id' => 1,
            'company_id' => 1,
            'original_loan_id' => 1,
            'new_loan_id' => 1,
        ]);
    }

    private function migration(): object
    {
        $migration = require database_path(
            'migrations/2026_07_27_000060_harden_loans_company_id.php'
        );

        foreach ([
            'loansTable' => $this->loansTable,
            'companiesTable' => $this->companiesTable,
            'customersTable' => $this->customersTable,
            'loanCustomerTenantForeignKey' => $this->customerForeignKey,
            'requiredReferencingForeignKeys' => [
                $this->paymentForeignKey => [
                    'table' => $this->paymentsTable,
                    'columns' => ['company_id', 'loan_id'],
                ],
                $this->originalLoanForeignKey => [
                    'table' => $this->restructuringsTable,
                    'columns' => ['company_id', 'original_loan_id'],
                ],
                $this->newLoanForeignKey => [
                    'table' => $this->restructuringsTable,
                    'columns' => ['company_id', 'new_loan_id'],
                ],
            ],
        ] as $property => $value) {
            $reflection = new \ReflectionProperty($migration, $property);
            $reflection->setValue($migration, $value);
        }

        return $migration;
    }

    private function dropCompanyForeignKey(): void
    {
        if ($this->findForeignKey($this->loansTable, $this->companyForeignKey) === null) {
            return;
        }

        DB::statement(
            "ALTER TABLE `{$this->loansTable}` "
            ."DROP FOREIGN KEY `{$this->companyForeignKey}`"
        );
    }

    private function assertCompanyForeignKeyRules(string $deleteRule): void
    {
        $foreignKey = $this->foreignKey(
            $this->loansTable,
            $this->companyForeignKey
        );

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

    private function dependentForeignKeys(): array
    {
        return [
            $this->paymentForeignKey => $this->foreignKey(
                $this->paymentsTable,
                $this->paymentForeignKey
            ),
            $this->originalLoanForeignKey => $this->foreignKey(
                $this->restructuringsTable,
                $this->originalLoanForeignKey
            ),
            $this->newLoanForeignKey => $this->foreignKey(
                $this->restructuringsTable,
                $this->newLoanForeignKey
            ),
        ];
    }

    private function foreignKey(string $table, string $name): array
    {
        $foreignKey = $this->findForeignKey($table, $name);
        $this->assertNotNull($foreignKey, "Foreign key {$name} was not found.");

        return $foreignKey;
    }

    private function findForeignKey(string $table, string $name): ?array
    {
        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if (strcasecmp($foreignKey['name'], $name) === 0) {
                return $foreignKey;
            }
        }

        return null;
    }

    private function hasLoanUnique(): bool
    {
        foreach (Schema::getIndexes($this->loansTable) as $index) {
            if (strcasecmp($index['name'], $this->loanUnique) === 0
                && $index['columns'] === ['company_id', 'id']
                && $index['unique']) {
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
        $column = collect(Schema::getColumns($this->loansTable))
            ->firstWhere('name', $name);
        $this->assertIsArray($column);

        return $column;
    }

    private function companyIdDefinition(): string
    {
        $row = (array) DB::selectOne("SHOW CREATE TABLE `{$this->loansTable}`");
        $createSql = array_values($row)[1];
        preg_match('/^\s*`company_id`\s+(.+?)(?:,\s*)?$/mi', $createSql, $matches);

        return trim($matches[1] ?? '');
    }

    private function rows(): array
    {
        return DB::table($this->loansTable)
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();
    }
}
