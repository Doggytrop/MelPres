<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class HardenRestructuringsCompanyIdTest extends TestCase
{
    private string $companiesTable = 'hr_companies';

    private string $usersTable = 'hr_users';

    private string $loansTable = 'hr_loans';

    private string $restructuringsTable = 'hr_restructurings';

    private string $companyForeignKey = 'hr_restructurings_company_id_foreign';

    private array $foreignKeys = [
        'new_tenant' => 'hr_restruct_new_tenant_fk',
        'original_tenant' => 'hr_restruct_original_tenant_fk',
        'recorded_tenant' => 'hr_restruct_recorded_tenant_fk',
        'new_simple' => 'hr_restruct_new_simple_fk',
        'original_simple' => 'hr_restruct_original_simple_fk',
        'recorded_simple' => 'hr_restruct_recorded_simple_fk',
    ];

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
        Schema::dropIfExists($this->restructuringsTable);
        Schema::dropIfExists($this->loansTable);
        Schema::dropIfExists($this->usersTable);
        Schema::dropIfExists($this->companiesTable);

        parent::tearDown();
    }

    public function test_up_is_idempotent_and_preserves_data_columns_indexes_and_fks(): void
    {
        $rows = $this->rows();
        $columns = $this->otherColumns();
        $indexes = Schema::getIndexes($this->restructuringsTable);
        $otherForeignKeys = $this->otherForeignKeys();

        $migration = $this->migration();
        $migration->up();
        $migration->up();

        $column = $this->column('company_id');
        $this->assertFalse($column['nullable']);
        $this->assertStringNotContainsString(
            'DEFAULT',
            strtoupper($this->companyIdDefinition())
        );
        $this->assertCompanyForeignKeyRules('RESTRICT');
        $this->assertSame($rows, $this->rows());
        $this->assertSame($columns, $this->otherColumns());
        $this->assertSame($indexes, Schema::getIndexes($this->restructuringsTable));
        $this->assertSame($otherForeignKeys, $this->otherForeignKeys());
        $this->assertFalse($this->hasCompanyIdUnique());
    }

    public function test_valid_and_optional_new_loan_values_are_accepted(): void
    {
        $this->migration()->up();

        $this->insertRestructuring(2, 2, 2, 2, 'valid');
        $this->insertRestructuring(2, 2, null, 2, 'without-new-loan');

        $this->assertDatabaseHas($this->restructuringsTable, [
            'company_id' => 2,
            'new_loan_id' => null,
            'marker' => 'without-new-loan',
        ]);
    }

    public function test_invalid_company_and_cross_tenant_values_are_rejected(): void
    {
        $this->migration()->up();

        $this->assertQueryFails(
            fn () => $this->insertRestructuring(null, 1, 1, 1, 'null-company')
        );
        $this->assertQueryFails(
            fn () => $this->insertRestructuring(999999, 1, 1, 1, 'orphan-company')
        );
        $this->assertQueryFails(
            fn () => $this->insertRestructuring(2, 1, 2, 2, 'cross-original')
        );
        $this->assertQueryFails(
            fn () => $this->insertRestructuring(2, 2, 1, 2, 'cross-new')
        );
        $this->assertQueryFails(
            fn () => $this->insertRestructuring(2, 2, 2, 1, 'cross-user')
        );
    }

    public function test_company_loan_and_user_delete_behaviour_remains_restricted(): void
    {
        $this->migration()->up();

        $this->assertQueryFails(
            fn () => DB::table($this->companiesTable)->where('id', 1)->delete()
        );
        $this->assertQueryFails(
            fn () => DB::table($this->loansTable)->where('id', 1)->delete()
        );
        $this->assertQueryFails(
            fn () => DB::table($this->usersTable)->where('id', 1)->delete()
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
            DB::table($this->restructuringsTable)->where('id', 1)->value('company_id')
        );
    }

    public function test_up_refuses_existing_null_company_ids(): void
    {
        $this->insertRestructuring(null, 1, 1, 1, 'legacy-null');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('NULL values');

        $this->migration()->up();
    }

    public function test_up_refuses_cross_company_original_loan_data(): void
    {
        $this->dropForeignKey($this->foreignKeys['original_tenant']);
        $this->insertRestructuring(2, 1, 2, 2, 'legacy-cross');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('original loan');

        $this->migration()->up();
    }

    public function test_up_completes_safe_partial_states(): void
    {
        $this->dropForeignKey($this->companyForeignKey);
        $migration = $this->migration();
        $migration->up();

        $this->assertFalse($this->column('company_id')['nullable']);
        $this->assertCompanyForeignKeyRules('RESTRICT');

        $this->dropForeignKey($this->companyForeignKey);
        $migration->down();

        $this->assertTrue($this->column('company_id')['nullable']);
        $this->assertCompanyForeignKeyRules('SET NULL');
    }

    public function test_up_refuses_unexpected_simple_company_fk_rules(): void
    {
        $this->dropForeignKey($this->companyForeignKey);
        DB::statement(
            "ALTER TABLE `{$this->restructuringsTable}` "
            ."ADD CONSTRAINT `{$this->companyForeignKey}` FOREIGN KEY (`company_id`) "
            ."REFERENCES `{$this->companiesTable}` (`id`) "
            .'ON UPDATE RESTRICT ON DELETE CASCADE'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unexpected rules');

        $this->migration()->up();
    }

    public function test_up_refuses_changed_non_company_fk(): void
    {
        $this->dropForeignKey($this->foreignKeys['new_tenant']);
        DB::statement(
            "ALTER TABLE `{$this->restructuringsTable}` "
            ."ADD CONSTRAINT `{$this->foreignKeys['new_tenant']}` "
            .'FOREIGN KEY (`company_id`, `new_loan_id`) '
            ."REFERENCES `{$this->loansTable}` (`company_id`, `id`) "
            .'ON UPDATE RESTRICT ON DELETE CASCADE'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unexpected definition');

        $this->migration()->up();
    }

    public function test_down_partial_state_preserves_rows_indexes_and_other_fks(): void
    {
        $migration = $this->migration();
        $migration->up();
        $rows = $this->rows();
        $indexes = Schema::getIndexes($this->restructuringsTable);
        $foreignKeys = $this->otherForeignKeys();
        $this->dropForeignKey($this->companyForeignKey);

        $migration->down();

        $this->assertSame($rows, $this->rows());
        $this->assertSame($indexes, Schema::getIndexes($this->restructuringsTable));
        $this->assertSame($foreignKeys, $this->otherForeignKeys());
    }

    private function createInitialSchema(): void
    {
        Schema::dropIfExists($this->restructuringsTable);
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
            $table->unique(['company_id', 'id'], 'hr_users_company_id_unique');
        });

        Schema::create($this->loansTable, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->unique(['company_id', 'id'], 'hr_loans_company_id_unique');
        });

        Schema::create($this->restructuringsTable, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->default(null);
            $table->unsignedBigInteger('original_loan_id');
            $table->unsignedBigInteger('new_loan_id')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->string('marker')->nullable();
            $table->index('original_loan_id', 'hr_restruct_original_idx');
            $table->index('new_loan_id', 'hr_restruct_new_idx');
            $table->index('recorded_by', 'hr_restruct_recorded_idx');
            $table->index(
                ['company_id', 'original_loan_id'],
                'hr_restruct_company_original_idx'
            );
            $table->index(
                ['company_id', 'new_loan_id'],
                'hr_restruct_company_new_idx'
            );
            $table->index(
                ['company_id', 'recorded_by'],
                'hr_restruct_company_recorded_idx'
            );
            $table->foreign('company_id', $this->companyForeignKey)
                ->references('id')->on($this->companiesTable)
                ->noActionOnUpdate()
                ->nullOnDelete();
            $table->foreign(
                ['company_id', 'original_loan_id'],
                $this->foreignKeys['original_tenant']
            )->references(['company_id', 'id'])->on($this->loansTable)
                ->restrictOnUpdate()->restrictOnDelete();
            $table->foreign(
                ['company_id', 'new_loan_id'],
                $this->foreignKeys['new_tenant']
            )->references(['company_id', 'id'])->on($this->loansTable)
                ->restrictOnUpdate()->restrictOnDelete();
            $table->foreign(
                ['company_id', 'recorded_by'],
                $this->foreignKeys['recorded_tenant']
            )->references(['company_id', 'id'])->on($this->usersTable)
                ->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('original_loan_id', $this->foreignKeys['original_simple'])
                ->references('id')->on($this->loansTable)
                ->noActionOnUpdate()->restrictOnDelete();
            $table->foreign('new_loan_id', $this->foreignKeys['new_simple'])
                ->references('id')->on($this->loansTable)
                ->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('recorded_by', $this->foreignKeys['recorded_simple'])
                ->references('id')->on($this->usersTable)
                ->noActionOnUpdate()->restrictOnDelete();
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
        $this->insertRestructuring(1, 1, 1, 1, 'existing');
    }

    private function migration(): object
    {
        $migration = require database_path(
            'migrations/2026_07_27_000090_harden_restructurings_company_id.php'
        );
        $expected = [
            $this->foreignKeys['new_tenant'] => [
                'columns' => ['company_id', 'new_loan_id'],
                'table' => $this->loansTable,
                'foreign_columns' => ['company_id', 'id'],
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ],
            $this->foreignKeys['original_tenant'] => [
                'columns' => ['company_id', 'original_loan_id'],
                'table' => $this->loansTable,
                'foreign_columns' => ['company_id', 'id'],
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ],
            $this->foreignKeys['recorded_tenant'] => [
                'columns' => ['company_id', 'recorded_by'],
                'table' => $this->usersTable,
                'foreign_columns' => ['company_id', 'id'],
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ],
            $this->foreignKeys['new_simple'] => [
                'columns' => ['new_loan_id'],
                'table' => $this->loansTable,
                'foreign_columns' => ['id'],
                'update' => 'RESTRICT',
                'delete' => 'RESTRICT',
            ],
            $this->foreignKeys['original_simple'] => [
                'columns' => ['original_loan_id'],
                'table' => $this->loansTable,
                'foreign_columns' => ['id'],
                'update' => 'NO ACTION',
                'delete' => 'RESTRICT',
            ],
            $this->foreignKeys['recorded_simple'] => [
                'columns' => ['recorded_by'],
                'table' => $this->usersTable,
                'foreign_columns' => ['id'],
                'update' => 'NO ACTION',
                'delete' => 'RESTRICT',
            ],
        ];

        foreach ([
            'restructuringsTable' => $this->restructuringsTable,
            'companiesTable' => $this->companiesTable,
            'loansTable' => $this->loansTable,
            'usersTable' => $this->usersTable,
            'expectedOtherForeignKeys' => $expected,
        ] as $property => $value) {
            $reflection = new \ReflectionProperty($migration, $property);
            $reflection->setValue($migration, $value);
        }

        return $migration;
    }

    private function insertRestructuring(
        ?int $companyId,
        int $originalLoanId,
        ?int $newLoanId,
        int $recordedBy,
        string $marker
    ): void {
        DB::table($this->restructuringsTable)->insert([
            'company_id' => $companyId,
            'original_loan_id' => $originalLoanId,
            'new_loan_id' => $newLoanId,
            'recorded_by' => $recordedBy,
            'marker' => $marker,
        ]);
    }

    private function dropForeignKey(string $name): void
    {
        if ($this->findForeignKey($name) === null) {
            return;
        }

        DB::statement(
            "ALTER TABLE `{$this->restructuringsTable}` DROP FOREIGN KEY `{$name}`"
        );
    }

    private function otherForeignKeys(): array
    {
        $foreignKeys = Schema::getForeignKeys($this->restructuringsTable);

        return array_values(array_filter(
            $foreignKeys,
            fn (array $foreignKey): bool => strcasecmp(
                $foreignKey['name'],
                $this->companyForeignKey
            ) !== 0
        ));
    }

    private function assertCompanyForeignKeyRules(string $deleteRule): void
    {
        $foreignKey = $this->foreignKey($this->companyForeignKey);
        $this->assertSame(['company_id'], $foreignKey['columns']);
        $this->assertSame($this->companiesTable, $foreignKey['foreign_table']);
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
        foreach (Schema::getForeignKeys($this->restructuringsTable) as $foreignKey) {
            if (strcasecmp($foreignKey['name'], $name) === 0) {
                return $foreignKey;
            }
        }

        return null;
    }

    private function hasCompanyIdUnique(): bool
    {
        foreach (Schema::getIndexes($this->restructuringsTable) as $index) {
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
        $column = collect(Schema::getColumns($this->restructuringsTable))
            ->firstWhere('name', $name);
        $this->assertIsArray($column);

        return $column;
    }

    private function companyIdDefinition(): string
    {
        $row = (array) DB::selectOne(
            "SHOW CREATE TABLE `{$this->restructuringsTable}`"
        );
        $createSql = array_values($row)[1];
        preg_match('/^\s*`company_id`\s+(.+?)(?:,\s*)?$/mi', $createSql, $matches);

        return trim($matches[1] ?? '');
    }

    private function rows(): array
    {
        return DB::table($this->restructuringsTable)
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();
    }

    private function otherColumns(): array
    {
        return array_values(array_filter(
            Schema::getColumns($this->restructuringsTable),
            fn (array $column): bool => $column['name'] !== 'company_id'
        ));
    }
}
