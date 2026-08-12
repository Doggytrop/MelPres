<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class HardenSettingsCompanyIdTest extends TestCase
{
    private string $companiesTable = 'hs_companies';

    private string $settingsTable = 'hs_settings';

    private string $foreignKey = 'hs_settings_company_id_foreign';

    private string $uniqueIndex = 'hs_settings_company_key_unique';

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
        Schema::dropIfExists($this->settingsTable);
        Schema::dropIfExists($this->companiesTable);

        parent::tearDown();
    }

    public function test_up_hardens_company_id_and_preserves_rows_columns_and_indexes(): void
    {
        $beforeRows = $this->rows();
        $beforeIndexes = $this->indexes();
        $beforeOtherColumns = $this->otherColumns();

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
        $this->assertForeignKeyRules('RESTRICT');
        $this->assertSame($beforeRows, $this->rows());
        $this->assertSame($beforeIndexes, $this->indexes());
        $this->assertSame($beforeOtherColumns, $this->otherColumns());
        $this->assertSame(1, $this->foreignKeyCount());
    }

    public function test_hardened_schema_accepts_valid_rows_and_rejects_null_or_orphan_companies(): void
    {
        $this->migration()->up();

        DB::table($this->settingsTable)->insert([
            'company_id' => 1,
            'key' => 'valid',
            'value' => 'yes',
            'type' => 'string',
            'group' => 'test',
            'marker' => 'valid-row',
        ]);

        $this->assertSame(
            'yes',
            DB::table($this->settingsTable)->where('key', 'valid')->value('value')
        );

        $this->assertQueryFails(fn () => DB::table($this->settingsTable)->insert([
            'company_id' => null,
            'key' => 'null-company',
            'type' => 'string',
            'group' => 'test',
        ]));

        $this->assertQueryFails(fn () => DB::table($this->settingsTable)->insert([
            'company_id' => 999999,
            'key' => 'orphan-company',
            'type' => 'string',
            'group' => 'test',
        ]));
    }

    public function test_referenced_company_is_restricted_but_unreferenced_company_can_be_deleted(): void
    {
        $this->migration()->up();

        $this->assertQueryFails(
            fn () => DB::table($this->companiesTable)->where('id', 1)->delete()
        );

        DB::table($this->companiesTable)->where('id', 3)->delete();

        $this->assertFalse(
            DB::table($this->companiesTable)->where('id', 3)->exists()
        );
    }

    public function test_down_restores_nullable_default_null_and_set_null_idempotently(): void
    {
        $migration = $this->migration();
        $migration->up();
        $migration->down();
        $migration->down();

        $companyId = $this->column('company_id');
        $this->assertTrue($companyId['nullable']);
        $this->assertMatchesRegularExpression(
            '/DEFAULT\s+NULL/i',
            $this->companyIdDefinition()
        );
        $this->assertForeignKeyRules('SET NULL');
        $this->assertSame(1, $this->foreignKeyCount());

        DB::table($this->settingsTable)->insert([
            'company_id' => null,
            'key' => 'global-after-down',
            'type' => 'string',
            'group' => 'test',
        ]);

        DB::table($this->companiesTable)->where('id', 1)->delete();

        $this->assertNull(
            DB::table($this->settingsTable)->where('id', 1)->value('company_id')
        );
    }

    public function test_up_refuses_existing_null_company_ids_without_changing_schema(): void
    {
        DB::table($this->settingsTable)->insert([
            'company_id' => null,
            'key' => 'legacy-null',
            'type' => 'string',
            'group' => 'test',
        ]);

        try {
            $this->migration()->up();
            $this->fail('The migration accepted a NULL company_id.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('NULL values', $exception->getMessage());
        }

        $this->assertTrue($this->column('company_id')['nullable']);
        $this->assertForeignKeyRules('SET NULL');
    }

    public function test_up_refuses_orphan_company_ids_without_changing_schema(): void
    {
        $this->dropForeignKey();
        DB::table($this->settingsTable)->insert([
            'company_id' => 999999,
            'key' => 'orphan',
            'type' => 'string',
            'group' => 'test',
        ]);

        try {
            $this->migration()->up();
            $this->fail('The migration accepted an orphan company_id.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Orphan company', $exception->getMessage());
        }

        $this->assertTrue($this->column('company_id')['nullable']);
        $this->assertNull($this->findForeignKey());
    }

    public function test_up_completes_safe_partial_state_with_missing_foreign_key(): void
    {
        $this->dropForeignKey();

        $this->migration()->up();

        $this->assertFalse($this->column('company_id')['nullable']);
        $this->assertForeignKeyRules('RESTRICT');
    }

    public function test_up_completes_safe_partial_state_after_column_was_altered(): void
    {
        $this->dropForeignKey();
        DB::statement(
            "ALTER TABLE `{$this->settingsTable}` "
            .'MODIFY COLUMN `company_id` BIGINT UNSIGNED NOT NULL'
        );

        $this->migration()->up();

        $this->assertFalse($this->column('company_id')['nullable']);
        $this->assertForeignKeyRules('RESTRICT');
    }

    public function test_up_refuses_an_unexpected_foreign_key_definition(): void
    {
        $this->dropForeignKey();
        DB::statement(
            "ALTER TABLE `{$this->settingsTable}` "
            ."ADD CONSTRAINT `{$this->foreignKey}` FOREIGN KEY (`company_id`) "
            ."REFERENCES `{$this->companiesTable}` (`id`) "
            .'ON UPDATE RESTRICT ON DELETE CASCADE'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unexpected update/delete rules');

        $this->migration()->up();
    }

    public function test_up_refuses_missing_or_duplicate_exact_unique_indexes(): void
    {
        $this->dropForeignKey();
        DB::statement(
            "ALTER TABLE `{$this->settingsTable}` DROP INDEX `{$this->uniqueIndex}`"
        );

        try {
            $this->migration()->up();
            $this->fail('The migration accepted a missing tenant unique index.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'exactly one UNIQUE index',
                $exception->getMessage()
            );
        }

        DB::statement(
            "ALTER TABLE `{$this->settingsTable}` "
            ."ADD UNIQUE INDEX `{$this->uniqueIndex}` (`company_id`, `key`), "
            .'ADD UNIQUE INDEX `hs_settings_duplicate_unique` (`company_id`, `key`)'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('exactly one UNIQUE index');

        $this->migration()->up();
    }

    public function test_up_refuses_an_unexpected_column_definition(): void
    {
        $this->dropForeignKey();
        DB::statement(
            "ALTER TABLE `{$this->settingsTable}` "
            .'MODIFY COLUMN `company_id` INT UNSIGNED NULL DEFAULT NULL'
        );

        $this->expectException(RuntimeException::class);

        $this->migration()->up();
    }

    public function test_down_completes_safe_partial_states_and_preserves_data(): void
    {
        $migration = $this->migration();
        $migration->up();
        $before = $this->rows();

        $this->dropForeignKey();
        $migration->down();

        $this->assertTrue($this->column('company_id')['nullable']);
        $this->assertForeignKeyRules('SET NULL');
        $this->assertSame($before, $this->rows());
    }

    private function createInitialSchema(): void
    {
        Schema::dropIfExists($this->settingsTable);
        Schema::dropIfExists($this->companiesTable);

        Schema::create($this->companiesTable, function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        Schema::create($this->settingsTable, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->default(null);
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group');
            $table->string('marker')->nullable();
            $table->unique(['company_id', 'key'], $this->uniqueIndex);
            $table->foreign('company_id', $this->foreignKey)
                ->references('id')
                ->on($this->companiesTable)
                ->noActionOnUpdate()
                ->nullOnDelete();
        });

        DB::table($this->companiesTable)->insert([
            ['id' => 1, 'name' => 'Company One'],
            ['id' => 2, 'name' => 'Company Two'],
            ['id' => 3, 'name' => 'Company Three'],
        ]);

        DB::table($this->settingsTable)->insert([
            [
                'id' => 1,
                'company_id' => 1,
                'key' => 'currency',
                'value' => 'MXN',
                'type' => 'string',
                'group' => 'general',
                'marker' => 'one',
            ],
            [
                'id' => 2,
                'company_id' => 2,
                'key' => 'currency',
                'value' => 'USD',
                'type' => 'string',
                'group' => 'general',
                'marker' => 'two',
            ],
        ]);
    }

    private function migration(): object
    {
        $migration = require database_path(
            'migrations/2026_07_27_000040_harden_settings_company_id.php'
        );

        foreach ([
            'settingsTable' => $this->settingsTable,
            'companiesTable' => $this->companiesTable,
        ] as $property => $value) {
            $reflection = new \ReflectionProperty($migration, $property);
            $reflection->setValue($migration, $value);
        }

        return $migration;
    }

    private function dropForeignKey(): void
    {
        if ($this->findForeignKey() === null) {
            return;
        }

        DB::statement(
            "ALTER TABLE `{$this->settingsTable}` DROP FOREIGN KEY `{$this->foreignKey}`"
        );
    }

    private function findForeignKey(): ?array
    {
        foreach (Schema::getForeignKeys($this->settingsTable) as $foreignKey) {
            if (strcasecmp($foreignKey['name'], $this->foreignKey) === 0) {
                return $foreignKey;
            }
        }

        return null;
    }

    private function foreignKeyCount(): int
    {
        return count(array_filter(
            Schema::getForeignKeys($this->settingsTable),
            fn (array $foreignKey): bool => strcasecmp(
                $foreignKey['name'],
                $this->foreignKey
            ) === 0
        ));
    }

    private function assertForeignKeyRules(string $deleteRule): void
    {
        $foreignKey = $this->findForeignKey();

        $this->assertNotNull($foreignKey);
        $this->assertSame(['company_id'], $foreignKey['columns']);
        $this->assertSame($this->companiesTable, $foreignKey['foreign_table']);
        $this->assertSame(['id'], $foreignKey['foreign_columns']);
        $this->assertContains(
            strtoupper($foreignKey['on_update']),
            ['RESTRICT', 'NO ACTION']
        );

        if ($deleteRule === 'RESTRICT') {
            $this->assertContains(
                strtoupper($foreignKey['on_delete']),
                ['RESTRICT', 'NO ACTION']
            );
        } else {
            $this->assertSame('SET NULL', strtoupper($foreignKey['on_delete']));
        }
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
        $column = collect(Schema::getColumns($this->settingsTable))
            ->firstWhere('name', $name);

        $this->assertIsArray($column);

        return $column;
    }

    private function companyIdDefinition(): string
    {
        $row = (array) DB::selectOne("SHOW CREATE TABLE `{$this->settingsTable}`");
        $createSql = array_values($row)[1];

        preg_match('/^\s*`company_id`\s+(.+?)(?:,\s*)?$/mi', $createSql, $matches);

        return trim($matches[1] ?? '');
    }

    private function indexes(): array
    {
        return Schema::getIndexes($this->settingsTable);
    }

    private function rows(): array
    {
        return DB::table($this->settingsTable)
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();
    }

    private function otherColumns(): array
    {
        return array_values(array_filter(
            Schema::getColumns($this->settingsTable),
            fn (array $column): bool => $column['name'] !== 'company_id'
        ));
    }
}
