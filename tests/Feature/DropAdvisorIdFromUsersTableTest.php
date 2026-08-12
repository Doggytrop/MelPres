<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class DropAdvisorIdFromUsersTableTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    private const CONSTRAINT = 'users_advisor_id_foreign';

    private const INDEX = 'users_advisor_id_foreign';

    public function runDatabaseMigrations()
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped(
                'La eliminacion de users.advisor_id requiere validacion definitiva sobre MySQL.'
            );
        }

        $database = strtolower((string) DB::getDatabaseName());

        if (! str_contains($database, 'test')) {
            $this->fail('Las pruebas DDL solo pueden ejecutarse en una base MySQL de pruebas.');
        }

        $this->baseRunDatabaseMigrations();
    }

    public function test_legacy_schema_contains_the_expected_column_index_and_foreign_key(): void
    {
        $this->migration()->down();

        $this->assertTrue(Schema::hasColumn('users', 'advisor_id'));
        $this->assertNotNull($this->exactIndex(['advisor_id']));
        $this->assertNotNull($this->namedIndex(self::INDEX));

        $foreignKey = $this->foreignKey(self::CONSTRAINT);

        $this->assertNotNull($foreignKey);
        $this->assertSame(['advisor_id'], $foreignKey['columns']);
        $this->assertSame('users', $foreignKey['foreign_table']);
        $this->assertSame(['id'], $foreignKey['foreign_columns']);
        $this->assertSame('set null', strtolower($foreignKey['on_delete']));
        $this->assertContains(strtoupper($foreignKey['on_update']), ['NO ACTION', 'RESTRICT']);
    }

    public function test_up_removes_only_advisor_schema_and_preserves_users_and_roles(): void
    {
        $migration = $this->migration();
        $migration->down();

        $adminId = $this->insertUser('admin@example.com', 'admin');
        $advisorId = $this->insertUser('advisor@example.com', 'advisor');

        $migration->up();

        $this->assertFalse(Schema::hasColumn('users', 'advisor_id'));
        $this->assertNull($this->foreignKey(self::CONSTRAINT));
        $this->assertNull($this->namedIndex(self::INDEX));

        foreach (['id', 'company_id', 'role', 'email', 'password'] as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column));
        }

        $this->assertSame('admin', DB::table('users')->find($adminId)->role);
        $this->assertSame('advisor', DB::table('users')->find($advisorId)->role);
        $this->assertSame(2, DB::table('users')->whereIn('id', [$adminId, $advisorId])->count());
    }

    public function test_up_aborts_without_changes_when_an_advisor_id_is_assigned(): void
    {
        $migration = $this->migration();
        $migration->down();

        $advisorId = $this->insertUser('assigned-advisor@example.com', 'advisor');
        $userId = $this->insertUser('assigned-user@example.com', 'customer', $advisorId);

        try {
            $migration->up();
            $this->fail('La migracion debio rechazar advisor_id no nulo.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'existen 1 filas con valor',
                $exception->getMessage()
            );
        }

        $this->assertTrue(Schema::hasColumn('users', 'advisor_id'));
        $this->assertNotNull($this->foreignKey(self::CONSTRAINT));
        $this->assertNotNull($this->exactIndex(['advisor_id']));
        $this->assertSame(
            $advisorId,
            DB::table('users')->where('id', $userId)->value('advisor_id')
        );
    }

    public function test_down_restores_exact_column_index_and_foreign_key_definition(): void
    {
        $migration = $this->migration();
        $migration->down();

        $column = DB::selectOne(
            'SELECT COLUMN_TYPE, IS_NULLABLE
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?',
            ['users', 'advisor_id']
        );

        $this->assertNotNull($column);
        $this->assertSame('bigint unsigned', strtolower($column->COLUMN_TYPE));
        $this->assertSame('YES', $column->IS_NULLABLE);

        $index = $this->exactIndex(['advisor_id']);

        $this->assertNotNull($index);
        $this->assertSame(self::INDEX, $index['name']);
        $this->assertFalse((bool) $index['unique']);

        $foreignKey = $this->foreignKey(self::CONSTRAINT);

        $this->assertNotNull($foreignKey);
        $this->assertSame(['advisor_id'], $foreignKey['columns']);
        $this->assertSame('users', $foreignKey['foreign_table']);
        $this->assertSame(['id'], $foreignKey['foreign_columns']);
        $this->assertSame('set null', strtolower($foreignKey['on_delete']));
        $this->assertContains(strtoupper($foreignKey['on_update']), ['NO ACTION', 'RESTRICT']);
    }

    public function test_repeated_up_and_down_are_idempotent(): void
    {
        $migration = $this->migration();

        $migration->down();
        $migration->up();
        $migration->up();

        $this->assertFalse(Schema::hasColumn('users', 'advisor_id'));

        $migration->down();
        $migration->down();

        $this->assertTrue(Schema::hasColumn('users', 'advisor_id'));
        $this->assertCount(1, $this->foreignKeysForColumn('advisor_id'));
        $this->assertCount(1, $this->indexesWithColumns(['advisor_id']));
    }

    public function test_is_advisor_depends_only_on_role(): void
    {
        $this->assertTrue((new User(['role' => 'advisor']))->isAdvisor());
        $this->assertFalse((new User(['role' => 'admin']))->isAdvisor());
    }

    private function migration(): object
    {
        return require database_path(
            'migrations/2026_07_27_000000_drop_advisor_id_from_users_table.php'
        );
    }

    private function insertUser(
        string $email,
        string $role,
        ?int $advisorId = null
    ): int {
        return DB::table('users')->insertGetId([
            'advisor_id' => $advisorId,
            'name' => ucfirst($role),
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => $role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function foreignKey(string $name): ?array
    {
        return collect(Schema::getForeignKeys('users'))
            ->first(fn (array $foreignKey) => strcasecmp($foreignKey['name'], $name) === 0);
    }

    private function foreignKeysForColumn(string $column): array
    {
        return array_values(array_filter(
            Schema::getForeignKeys('users'),
            fn (array $foreignKey) => in_array(
                strtolower($column),
                array_map('strtolower', $foreignKey['columns']),
                true
            )
        ));
    }

    private function exactIndex(array $columns): ?array
    {
        return collect($this->indexesWithColumns($columns))
            ->first(fn (array $index) => ! (bool) $index['unique']);
    }

    private function namedIndex(string $name): ?array
    {
        return collect(Schema::getIndexes('users'))
            ->first(fn (array $index) => strcasecmp($index['name'], $name) === 0);
    }

    private function indexesWithColumns(array $columns): array
    {
        return array_values(array_filter(
            Schema::getIndexes('users'),
            fn (array $index) => array_map('strtolower', $index['columns'])
                === array_map('strtolower', $columns)
        ));
    }
}
