<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class HardenCustomerDocumentsCompanyIdTest extends TestCase
{
    private string $companiesTable = 'hd_companies';

    private string $customersTable = 'hd_customers';

    private string $documentsTable = 'hd_documents';

    private string $companyForeignKey = 'hd_documents_company_id_foreign';

    private string $customerForeignKey = 'hd_documents_customer_id_foreign';

    private string $tenantForeignKey = 'hd_documents_customer_tenant_fk';

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
        Schema::dropIfExists($this->customersTable);
        Schema::dropIfExists($this->companiesTable);

        parent::tearDown();
    }

    public function test_up_is_idempotent_and_preserves_data_columns_indexes_and_fks(): void
    {
        $rows = $this->rows();
        $columns = $this->otherColumns();
        $indexes = Schema::getIndexes($this->documentsTable);
        $tenantForeignKey = $this->foreignKey($this->tenantForeignKey);
        $customerForeignKey = $this->foreignKey($this->customerForeignKey);

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
        $this->assertSame($indexes, Schema::getIndexes($this->documentsTable));
        $this->assertSame(
            $tenantForeignKey,
            $this->foreignKey($this->tenantForeignKey)
        );
        $this->assertSame(
            $customerForeignKey,
            $this->foreignKey($this->customerForeignKey)
        );
        $this->assertTenantForeignKeyIsCascade();
        $this->assertFalse($this->hasCompanyIdUnique());
    }

    public function test_valid_document_is_accepted_and_invalid_tenant_values_are_rejected(): void
    {
        $this->migration()->up();

        $this->insertDocument(2, 2, 'valid');

        $this->assertQueryFails(fn () => $this->insertDocument(null, 1, 'null'));
        $this->assertQueryFails(
            fn () => $this->insertDocument(999999, 1, 'orphan')
        );
        $this->assertQueryFails(
            fn () => $this->insertDocument(2, 1, 'cross-company')
        );
    }

    public function test_company_delete_is_restricted_and_customer_delete_cascades(): void
    {
        $this->migration()->up();

        $this->assertQueryFails(
            fn () => DB::table($this->companiesTable)->where('id', 1)->delete()
        );

        DB::table($this->customersTable)->where('id', 1)->delete();

        $this->assertFalse(
            DB::table($this->documentsTable)->where('id', 1)->exists()
        );
        $this->assertTenantForeignKeyIsCascade();
    }

    public function test_down_restores_nullable_set_null_and_preserves_cascade(): void
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
        $this->assertTenantForeignKeyIsCascade();
        $this->assertFalse($this->hasCompanyIdUnique());

        DB::table($this->companiesTable)->where('id', 1)->delete();

        $this->assertNull(
            DB::table($this->documentsTable)->where('id', 1)->value('company_id')
        );
    }

    public function test_up_refuses_existing_null_company_ids(): void
    {
        $this->insertDocument(null, 1, 'legacy-null');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('NULL values');

        $this->migration()->up();
    }

    public function test_up_refuses_cross_company_customer_data(): void
    {
        DB::statement(
            "ALTER TABLE `{$this->documentsTable}` "
            ."DROP FOREIGN KEY `{$this->tenantForeignKey}`"
        );
        $this->insertDocument(2, 1, 'legacy-cross');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cross-company');

        $this->migration()->up();
    }

    public function test_up_completes_safe_partial_state_without_simple_fk(): void
    {
        $this->dropCompanyForeignKey();

        $this->migration()->up();

        $this->assertFalse($this->column('company_id')['nullable']);
        $this->assertCompanyForeignKeyRules('RESTRICT');
        $this->assertTenantForeignKeyIsCascade();
    }

    public function test_up_completes_safe_partial_state_after_column_change(): void
    {
        $this->dropCompanyForeignKey();
        DB::statement(
            "ALTER TABLE `{$this->documentsTable}` "
            .'MODIFY COLUMN `company_id` BIGINT UNSIGNED NOT NULL'
        );

        $this->migration()->up();

        $this->assertFalse($this->column('company_id')['nullable']);
        $this->assertCompanyForeignKeyRules('RESTRICT');
        $this->assertTenantForeignKeyIsCascade();
    }

    public function test_up_refuses_unexpected_simple_fk_rules(): void
    {
        $this->dropCompanyForeignKey();
        DB::statement(
            "ALTER TABLE `{$this->documentsTable}` "
            ."ADD CONSTRAINT `{$this->companyForeignKey}` FOREIGN KEY (`company_id`) "
            ."REFERENCES `{$this->companiesTable}` (`id`) "
            .'ON UPDATE RESTRICT ON DELETE CASCADE'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unexpected rules');

        $this->migration()->up();
    }

    public function test_up_refuses_if_tenant_fk_is_not_delete_cascade(): void
    {
        DB::statement(
            "ALTER TABLE `{$this->documentsTable}` "
            ."DROP FOREIGN KEY `{$this->tenantForeignKey}`"
        );
        DB::statement(
            "ALTER TABLE `{$this->documentsTable}` "
            ."ADD CONSTRAINT `{$this->tenantForeignKey}` "
            .'FOREIGN KEY (`company_id`, `customer_id`) '
            ."REFERENCES `{$this->customersTable}` (`company_id`, `id`) "
            .'ON UPDATE RESTRICT ON DELETE RESTRICT'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DELETE CASCADE');

        $this->migration()->up();
    }

    public function test_down_completes_safe_partial_state_without_changes(): void
    {
        $migration = $this->migration();
        $migration->up();
        $rows = $this->rows();
        $indexes = Schema::getIndexes($this->documentsTable);
        $this->dropCompanyForeignKey();

        $migration->down();

        $this->assertTrue($this->column('company_id')['nullable']);
        $this->assertCompanyForeignKeyRules('SET NULL');
        $this->assertTenantForeignKeyIsCascade();
        $this->assertSame($rows, $this->rows());
        $this->assertSame($indexes, Schema::getIndexes($this->documentsTable));
    }

    private function createInitialSchema(): void
    {
        Schema::dropIfExists($this->documentsTable);
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
            $table->unique(['company_id', 'id'], 'hd_customers_company_id_unique');
        });

        Schema::create($this->documentsTable, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->default(null);
            $table->unsignedBigInteger('customer_id');
            $table->string('type');
            $table->string('path');
            $table->string('marker')->nullable();
            $table->index('customer_id', 'hd_documents_customer_id_idx');
            $table->index(
                ['company_id', 'customer_id', 'type', 'id'],
                'hd_documents_tenant_type_idx'
            );
            $table->foreign('company_id', $this->companyForeignKey)
                ->references('id')->on($this->companiesTable)
                ->noActionOnUpdate()
                ->nullOnDelete();
            $table->foreign('customer_id', $this->customerForeignKey)
                ->references('id')->on($this->customersTable)
                ->noActionOnUpdate()
                ->cascadeOnDelete();
            $table->foreign(
                ['company_id', 'customer_id'],
                $this->tenantForeignKey
            )
                ->references(['company_id', 'id'])
                ->on($this->customersTable)
                ->restrictOnUpdate()
                ->cascadeOnDelete();
        });

        DB::table($this->companiesTable)->insert([
            ['id' => 1, 'name' => 'Company One'],
            ['id' => 2, 'name' => 'Company Two'],
        ]);
        DB::table($this->customersTable)->insert([
            ['id' => 1, 'company_id' => 1, 'name' => 'Customer One'],
            ['id' => 2, 'company_id' => 2, 'name' => 'Customer Two'],
        ]);
        $this->insertDocument(1, 1, 'existing');
    }

    private function migration(): object
    {
        $migration = require database_path(
            'migrations/2026_07_27_000080_harden_customer_documents_company_id.php'
        );

        foreach ([
            'documentsTable' => $this->documentsTable,
            'customersTable' => $this->customersTable,
            'companiesTable' => $this->companiesTable,
            'customerTenantForeignKey' => $this->tenantForeignKey,
        ] as $property => $value) {
            $reflection = new \ReflectionProperty($migration, $property);
            $reflection->setValue($migration, $value);
        }

        return $migration;
    }

    private function insertDocument(
        ?int $companyId,
        int $customerId,
        string $marker
    ): void {
        DB::table($this->documentsTable)->insert([
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'type' => 'profile_photo',
            'path' => "documents/{$marker}.pdf",
            'marker' => $marker,
        ]);
    }

    private function dropCompanyForeignKey(): void
    {
        if ($this->findForeignKey($this->companyForeignKey) === null) {
            return;
        }

        DB::statement(
            "ALTER TABLE `{$this->documentsTable}` "
            ."DROP FOREIGN KEY `{$this->companyForeignKey}`"
        );
    }

    private function assertTenantForeignKeyIsCascade(): void
    {
        $foreignKey = $this->foreignKey($this->tenantForeignKey);

        $this->assertSame(['company_id', 'customer_id'], $foreignKey['columns']);
        $this->assertSame($this->customersTable, $foreignKey['foreign_table']);
        $this->assertSame(['company_id', 'id'], $foreignKey['foreign_columns']);
        $this->assertSame('RESTRICT', strtoupper($foreignKey['on_update']));
        $this->assertSame('CASCADE', strtoupper($foreignKey['on_delete']));
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
        foreach (Schema::getForeignKeys($this->documentsTable) as $foreignKey) {
            if (strcasecmp($foreignKey['name'], $name) === 0) {
                return $foreignKey;
            }
        }

        return null;
    }

    private function hasCompanyIdUnique(): bool
    {
        foreach (Schema::getIndexes($this->documentsTable) as $index) {
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
        $column = collect(Schema::getColumns($this->documentsTable))
            ->firstWhere('name', $name);
        $this->assertIsArray($column);

        return $column;
    }

    private function companyIdDefinition(): string
    {
        $row = (array) DB::selectOne("SHOW CREATE TABLE `{$this->documentsTable}`");
        $createSql = array_values($row)[1];
        preg_match('/^\s*`company_id`\s+(.+?)(?:,\s*)?$/mi', $createSql, $matches);

        return trim($matches[1] ?? '');
    }

    private function rows(): array
    {
        return DB::table($this->documentsTable)
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();
    }

    private function otherColumns(): array
    {
        return array_values(array_filter(
            Schema::getColumns($this->documentsTable),
            fn (array $column): bool => $column['name'] !== 'company_id'
        ));
    }
}
