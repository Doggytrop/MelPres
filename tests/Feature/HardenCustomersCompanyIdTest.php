<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class HardenCustomersCompanyIdTest extends TestCase
{
    private string $companiesTable = 'hc_companies';

    private string $customersTable = 'hc_customers';

    private string $loansTable = 'hc_loans';

    private string $documentsTable = 'hc_documents';

    private string $companyForeignKey = 'hc_customers_company_id_foreign';

    private string $customerUnique = 'hc_customers_company_id_unique';

    private string $loanForeignKey = 'hc_loans_customer_tenant_fk';

    private string $documentForeignKey = 'hc_docs_customer_tenant_fk';

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
        Schema::dropIfExists($this->documentsTable);
        Schema::dropIfExists($this->loansTable);
        Schema::dropIfExists($this->customersTable);
        Schema::dropIfExists($this->companiesTable);

        parent::tearDown();
    }

    public function test_up_is_idempotent_and_preserves_data_indexes_and_dependent_foreign_keys(): void
    {
        $rows = $this->rows();
        $indexes = Schema::getIndexes($this->customersTable);
        $loanForeignKey = $this->foreignKey($this->loansTable, $this->loanForeignKey);
        $documentForeignKey = $this->foreignKey(
            $this->documentsTable,
            $this->documentForeignKey
        );

        $migration = $this->migration();
        $migration->up();
        $migration->up();

        $companyId = $this->column('company_id');
        $this->assertSame('bigint unsigned', strtolower($companyId['type']));
        $this->assertFalse($companyId['nullable']);
        $this->assertStringNotContainsString(
            'DEFAULT',
            strtoupper($this->companyIdDefinition())
        );
        $this->assertCompanyForeignKeyRules('RESTRICT');
        $this->assertSame($rows, $this->rows());
        $this->assertSame($indexes, Schema::getIndexes($this->customersTable));
        $this->assertSame(
            $loanForeignKey,
            $this->foreignKey($this->loansTable, $this->loanForeignKey)
        );
        $this->assertSame(
            $documentForeignKey,
            $this->foreignKey($this->documentsTable, $this->documentForeignKey)
        );
        $this->assertTrue($this->hasCustomerUnique());
    }

    public function test_hardened_schema_rejects_null_or_orphan_company_ids(): void
    {
        $this->migration()->up();

        $this->assertQueryFails(fn () => DB::table($this->customersTable)->insert([
            'company_id' => null,
            'name' => 'Null company',
        ]));

        $this->assertQueryFails(fn () => DB::table($this->customersTable)->insert([
            'company_id' => 999999,
            'name' => 'Orphan company',
        ]));

        $this->assertQueryFails(
            fn () => DB::table($this->companiesTable)->where('id', 1)->delete()
        );
    }

    public function test_composite_foreign_keys_still_reject_cross_company_relations(): void
    {
        $this->migration()->up();

        $this->assertQueryFails(fn () => DB::table($this->loansTable)->insert([
            'company_id' => 2,
            'customer_id' => 1,
        ]));

        $this->assertQueryFails(fn () => DB::table($this->documentsTable)->insert([
            'company_id' => 2,
            'customer_id' => 1,
        ]));
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
        $this->assertTrue($this->hasCustomerUnique());

        DB::table($this->customersTable)->insert([
            'company_id' => null,
            'name' => 'Global after rollback',
        ]);

        DB::table($this->companiesTable)->where('id', 2)->delete();

        $this->assertNull(
            DB::table($this->customersTable)->where('id', 2)->value('company_id')
        );
    }

    public function test_up_refuses_null_company_ids(): void
    {
        DB::table($this->customersTable)->insert([
            'company_id' => null,
            'name' => 'Legacy null',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('NULL values');

        $this->migration()->up();
    }

    public function test_up_refuses_orphan_company_ids(): void
    {
        $this->dropCompanyForeignKey();
        DB::table($this->customersTable)->insert([
            'company_id' => 999999,
            'name' => 'Orphan',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Orphan company');

        $this->migration()->up();
    }

    public function test_up_completes_safe_partial_state_without_company_foreign_key(): void
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
            "ALTER TABLE `{$this->customersTable}` "
            .'MODIFY COLUMN `company_id` BIGINT UNSIGNED NOT NULL'
        );

        $this->migration()->up();

        $this->assertFalse($this->column('company_id')['nullable']);
        $this->assertCompanyForeignKeyRules('RESTRICT');
    }

    public function test_up_refuses_unexpected_company_foreign_key_rules(): void
    {
        $this->dropCompanyForeignKey();
        DB::statement(
            "ALTER TABLE `{$this->customersTable}` "
            ."ADD CONSTRAINT `{$this->companyForeignKey}` FOREIGN KEY (`company_id`) "
            ."REFERENCES `{$this->companiesTable}` (`id`) "
            .'ON UPDATE RESTRICT ON DELETE CASCADE'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unexpected update/delete rules');

        $this->migration()->up();
    }

    public function test_up_refuses_duplicate_customer_tenant_unique(): void
    {
        DB::statement(
            "ALTER TABLE `{$this->customersTable}` "
            .'ADD UNIQUE INDEX `hc_customers_duplicate_unique` (`company_id`, `id`)'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('exactly one UNIQUE index');

        $this->migration()->up();
    }

    public function test_up_refuses_an_unexpected_dependent_foreign_key(): void
    {
        DB::statement(
            "ALTER TABLE `{$this->documentsTable}` "
            ."DROP FOREIGN KEY `{$this->documentForeignKey}`"
        );
        DB::statement(
            "ALTER TABLE `{$this->documentsTable}` "
            ."ADD CONSTRAINT `{$this->documentForeignKey}` "
            .'FOREIGN KEY (`company_id`, `customer_id`) '
            ."REFERENCES `{$this->customersTable}` (`company_id`, `id`) "
            .'ON UPDATE RESTRICT ON DELETE RESTRICT'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unexpected rules');

        $this->migration()->up();
    }

    public function test_down_completes_safe_partial_state_and_preserves_rows(): void
    {
        $migration = $this->migration();
        $migration->up();
        $rows = $this->rows();
        $this->dropCompanyForeignKey();

        $migration->down();

        $this->assertTrue($this->column('company_id')['nullable']);
        $this->assertCompanyForeignKeyRules('SET NULL');
        $this->assertSame($rows, $this->rows());
    }

    private function createInitialSchema(): void
    {
        Schema::dropIfExists($this->documentsTable);
        Schema::dropIfExists($this->loansTable);
        Schema::dropIfExists($this->customersTable);
        Schema::dropIfExists($this->companiesTable);

        Schema::create($this->companiesTable, function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        Schema::create($this->customersTable, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->default(null);
            $table->string('name');
            $table->string('marker')->nullable();
            $table->unique(['company_id', 'id'], $this->customerUnique);
            $table->index(['company_id', 'name'], 'hc_customers_company_name_idx');
            $table->foreign('company_id', $this->companyForeignKey)
                ->references('id')->on($this->companiesTable)
                ->noActionOnUpdate()
                ->nullOnDelete();
        });

        Schema::create($this->loansTable, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id');
            $table->index(['company_id', 'customer_id'], 'hc_loans_customer_idx');
            $table->foreign(
                ['company_id', 'customer_id'],
                $this->loanForeignKey
            )
                ->references(['company_id', 'id'])
                ->on($this->customersTable)
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });

        Schema::create($this->documentsTable, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id');
            $table->index(['company_id', 'customer_id'], 'hc_docs_customer_idx');
            $table->foreign(
                ['company_id', 'customer_id'],
                $this->documentForeignKey
            )
                ->references(['company_id', 'id'])
                ->on($this->customersTable)
                ->restrictOnUpdate()
                ->cascadeOnDelete();
        });

        DB::table($this->companiesTable)->insert([
            ['id' => 1, 'name' => 'Company One'],
            ['id' => 2, 'name' => 'Company Two'],
            ['id' => 3, 'name' => 'Company Three'],
        ]);
        DB::table($this->customersTable)->insert([
            ['id' => 1, 'company_id' => 1, 'name' => 'Customer One', 'marker' => 'one'],
            ['id' => 2, 'company_id' => 2, 'name' => 'Customer Two', 'marker' => 'two'],
        ]);
        DB::table($this->loansTable)->insert([
            'id' => 1,
            'company_id' => 1,
            'customer_id' => 1,
        ]);
        DB::table($this->documentsTable)->insert([
            'id' => 1,
            'company_id' => 1,
            'customer_id' => 1,
        ]);
    }

    private function migration(): object
    {
        $migration = require database_path(
            'migrations/2026_07_27_000050_harden_customers_company_id.php'
        );

        foreach ([
            'customersTable' => $this->customersTable,
            'companiesTable' => $this->companiesTable,
            'loansTable' => $this->loansTable,
            'documentsTable' => $this->documentsTable,
        ] as $property => $value) {
            $reflection = new \ReflectionProperty($migration, $property);
            $reflection->setValue($migration, $value);
        }

        return $migration;
    }

    private function dropCompanyForeignKey(): void
    {
        if ($this->findForeignKey($this->customersTable, $this->companyForeignKey) === null) {
            return;
        }

        DB::statement(
            "ALTER TABLE `{$this->customersTable}` "
            ."DROP FOREIGN KEY `{$this->companyForeignKey}`"
        );
    }

    private function assertCompanyForeignKeyRules(string $deleteRule): void
    {
        $foreignKey = $this->foreignKey(
            $this->customersTable,
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

    private function hasCustomerUnique(): bool
    {
        foreach (Schema::getIndexes($this->customersTable) as $index) {
            if (strcasecmp($index['name'], $this->customerUnique) === 0
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
        $column = collect(Schema::getColumns($this->customersTable))
            ->firstWhere('name', $name);
        $this->assertIsArray($column);

        return $column;
    }

    private function companyIdDefinition(): string
    {
        $row = (array) DB::selectOne("SHOW CREATE TABLE `{$this->customersTable}`");
        $createSql = array_values($row)[1];
        preg_match('/^\s*`company_id`\s+(.+?)(?:,\s*)?$/mi', $createSql, $matches);

        return trim($matches[1] ?? '');
    }

    private function rows(): array
    {
        return DB::table($this->customersTable)
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();
    }
}
