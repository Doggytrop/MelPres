<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class HardenUsersCustomerIdTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    private const CUSTOMER_UNIQUE = 'users_customer_id_unique';

    private const TENANT_INDEX = 'users_company_customer_idx';

    private const CUSTOMER_FOREIGN = 'users_customer_id_foreign';

    private const TENANT_FOREIGN = 'users_company_customer_tenant_fk';

    private const INSERT_TRIGGER = 'users_customer_tenant_guard_bi';

    private const UPDATE_TRIGGER = 'users_customer_tenant_guard_bu';

    protected function runDatabaseMigrations(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('This test requires MySQL trigger and foreign key semantics.');
        }

        if (! str_contains(
            strtolower((string) DB::connection()->getDatabaseName()),
            'test'
        )) {
            $this->fail(
                'Refusing to alter a MySQL database whose name does not contain test.'
            );
        }

        $this->baseRunDatabaseMigrations();
    }

    public function test_up_creates_all_expected_objects_with_exact_definitions(): void
    {
        $customerUnique = $this->index(self::CUSTOMER_UNIQUE);
        $tenantIndex = $this->index(self::TENANT_INDEX);
        $simple = $this->foreignKey(self::CUSTOMER_FOREIGN);
        $tenant = $this->foreignKey(self::TENANT_FOREIGN);
        $insertTrigger = $this->trigger(self::INSERT_TRIGGER);
        $updateTrigger = $this->trigger(self::UPDATE_TRIGGER);

        $this->assertSame(['customer_id'], $customerUnique['columns']);
        $this->assertTrue($customerUnique['unique']);
        $this->assertSame(['company_id', 'customer_id'], $tenantIndex['columns']);
        $this->assertFalse($tenantIndex['unique']);
        $this->assertIndexVisible(self::CUSTOMER_UNIQUE);
        $this->assertIndexVisible(self::TENANT_INDEX);

        $this->assertForeignKey(
            $simple,
            ['customer_id'],
            'customers',
            ['id']
        );
        $this->assertForeignKey(
            $tenant,
            ['company_id', 'customer_id'],
            'customers',
            ['company_id', 'id']
        );

        $this->assertTrigger($insertTrigger, 'INSERT');
        $this->assertTrigger($updateTrigger, 'UPDATE');

        $customerColumn = $this->column('users', 'customer_id');
        $companyColumn = $this->column('users', 'company_id');

        $this->assertSame('bigint unsigned', strtolower($customerColumn->COLUMN_TYPE));
        $this->assertSame('YES', $customerColumn->IS_NULLABLE);
        $this->assertNull($customerColumn->COLUMN_DEFAULT);
        $this->assertSame('YES', $companyColumn->IS_NULLABLE);
    }

    public function test_valid_null_global_and_unique_cardinality_behaviour(): void
    {
        $tenantA = $this->createTenant('valid-a');
        $tenantB = $this->createTenant('valid-b');

        $validUser = $this->insertUser(
            $tenantA['company_id'],
            $tenantA['customer_id'],
            'customer',
            'valid-linked'
        );
        $nullUserA = $this->insertUser(
            $tenantA['company_id'],
            null,
            'admin',
            'valid-null-a'
        );
        $nullUserB = $this->insertUser(
            $tenantB['company_id'],
            null,
            'collector',
            'valid-null-b'
        );
        $globalUser = $this->insertUser(null, null, 'superadmin', 'valid-global');

        foreach ([$validUser, $nullUserA, $nullUserB, $globalUser] as $userId) {
            $this->assertDatabaseHas('users', ['id' => $userId]);
        }

        $this->assertQueryFails(fn () => $this->insertUser(
            $tenantA['company_id'],
            999999999,
            'customer',
            'invalid-orphan'
        ));
        $this->assertQueryFails(fn () => $this->insertUser(
            $tenantA['company_id'],
            $tenantB['customer_id'],
            'customer',
            'invalid-cross'
        ));
        $this->assertQueryFails(fn () => $this->insertUser(
            null,
            $tenantA['customer_id'],
            'customer',
            'invalid-global'
        ));
        $this->assertQueryFails(fn () => $this->insertUser(
            $tenantA['company_id'],
            $tenantA['customer_id'],
            'customer',
            'invalid-duplicate'
        ));
    }

    public function test_customer_delete_is_restricted_but_soft_delete_remains_valid(): void
    {
        $tenant = $this->createTenant('delete');
        $userId = $this->insertUser(
            $tenant['company_id'],
            $tenant['customer_id'],
            'customer',
            'delete-linked'
        );

        DB::table('customers')
            ->where('id', $tenant['customer_id'])
            ->update(['deleted_at' => now()]);

        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'customer_id' => $tenant['customer_id'],
        ]);
        $this->assertNotNull(
            DB::table('customers')
                ->where('id', $tenant['customer_id'])
                ->value('deleted_at')
        );

        $this->assertQueryFails(
            fn () => DB::table('customers')
                ->where('id', $tenant['customer_id'])
                ->delete()
        );
    }

    public function test_update_trigger_rejects_clearing_company_on_a_linked_user(): void
    {
        $tenant = $this->createTenant('update-trigger');
        $userId = $this->insertUser(
            $tenant['company_id'],
            $tenant['customer_id'],
            'customer',
            'update-trigger-linked'
        );

        $this->assertQueryFails(
            fn () => DB::table('users')
                ->where('id', $userId)
                ->update(['company_id' => null])
        );

        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'company_id' => $tenant['company_id'],
            'customer_id' => $tenant['customer_id'],
        ]);
    }

    public function test_up_is_idempotent_and_preserves_existing_data_and_schema(): void
    {
        $migration = $this->migration();
        $tenant = $this->createTenant('preserve');
        $userId = $this->insertUser(
            $tenant['company_id'],
            $tenant['customer_id'],
            'customer',
            'preserve-linked'
        );
        $rows = $this->users();
        $columns = Schema::getColumns('users');
        $otherIndexes = $this->otherIndexes();
        $otherForeignKeys = $this->otherForeignKeys();

        $migration->up();
        $migration->up();

        $this->assertSame($rows, $this->users());
        $this->assertSame($columns, Schema::getColumns('users'));
        $this->assertSame($otherIndexes, $this->otherIndexes());
        $this->assertSame($otherForeignKeys, $this->otherForeignKeys());
        $this->assertDatabaseHas('users', ['id' => $userId]);
        $this->assertSame(1, $this->namedIndexCount(self::CUSTOMER_UNIQUE));
        $this->assertSame(1, $this->namedForeignKeyCount(self::TENANT_FOREIGN));
        $this->assertSame(1, $this->namedTriggerCount(self::INSERT_TRIGGER));
        $this->assertSame(1, $this->namedTriggerCount(self::UPDATE_TRIGGER));
    }

    public function test_down_removes_only_new_objects_and_is_idempotent(): void
    {
        $migration = $this->migration();
        $tenant = $this->createTenant('rollback');
        $this->insertUser(
            $tenant['company_id'],
            $tenant['customer_id'],
            'customer',
            'rollback-linked'
        );
        $rows = $this->users();
        $columns = Schema::getColumns('users');
        $otherIndexes = $this->otherIndexes();
        $otherForeignKeys = $this->otherForeignKeys();

        try {
            $migration->down();
            $migration->down();

            $this->assertNull($this->findIndex(self::CUSTOMER_UNIQUE));
            $this->assertNull($this->findIndex(self::TENANT_INDEX));
            $this->assertNull($this->findForeignKey(self::CUSTOMER_FOREIGN));
            $this->assertNull($this->findForeignKey(self::TENANT_FOREIGN));
            $this->assertNull($this->findTrigger(self::INSERT_TRIGGER));
            $this->assertNull($this->findTrigger(self::UPDATE_TRIGGER));
            $this->assertSame($rows, $this->users());
            $this->assertSame($columns, Schema::getColumns('users'));
            $this->assertSame($otherIndexes, $this->otherIndexes());
            $this->assertSame($otherForeignKeys, $this->otherForeignKeys());
        } finally {
            $migration->up();
        }
    }

    public function test_up_completes_safe_partial_states(): void
    {
        $migration = $this->migration();

        try {
            $migration->down();

            Schema::table('users', function ($table): void {
                $table->unique('customer_id', self::CUSTOMER_UNIQUE);
                $table->index(
                    ['company_id', 'customer_id'],
                    self::TENANT_INDEX
                );
                $table->foreign('customer_id', self::CUSTOMER_FOREIGN)
                    ->references('id')
                    ->on('customers')
                    ->restrictOnUpdate()
                    ->restrictOnDelete();
                $table->foreign(
                    ['company_id', 'customer_id'],
                    self::TENANT_FOREIGN
                )
                    ->references(['company_id', 'id'])
                    ->on('customers')
                    ->restrictOnUpdate()
                    ->restrictOnDelete();
            });

            $this->assertNull($this->findTrigger(self::INSERT_TRIGGER));
            $this->assertNull($this->findTrigger(self::UPDATE_TRIGGER));

            $migration->up();

            $this->assertNotNull($this->findForeignKey(self::CUSTOMER_FOREIGN));
            $this->assertNotNull($this->findForeignKey(self::TENANT_FOREIGN));
            $this->assertNotNull($this->findTrigger(self::INSERT_TRIGGER));
            $this->assertNotNull($this->findTrigger(self::UPDATE_TRIGGER));
        } finally {
            if ($this->findTrigger(self::INSERT_TRIGGER) === null
                || $this->findTrigger(self::UPDATE_TRIGGER) === null
                || $this->findForeignKey(self::CUSTOMER_FOREIGN) === null
                || $this->findForeignKey(self::TENANT_FOREIGN) === null) {
                $migration->up();
            }
        }
    }

    public function test_up_rejects_unexpected_existing_definitions(): void
    {
        $migration = $this->migration();
        $caught = null;

        try {
            $migration->down();

            Schema::table('users', function ($table): void {
                $table->index('company_id', self::TENANT_INDEX);
            });

            try {
                $migration->up();
            } catch (RuntimeException $exception) {
                $caught = $exception;
            }

            $this->assertNotNull($caught);
            $this->assertStringContainsString(
                self::TENANT_INDEX,
                $caught->getMessage()
            );
        } finally {
            if ($this->findIndex(self::TENANT_INDEX) !== null) {
                Schema::table('users', function ($table): void {
                    $table->dropIndex(self::TENANT_INDEX);
                });
            }

            $migration->up();
        }
    }

    public function test_up_rejects_an_unexpected_trigger_definition(): void
    {
        $migration = $this->migration();
        $caught = null;

        try {
            $migration->down();
            DB::unprepared(
                'CREATE TRIGGER `'.self::INSERT_TRIGGER.'` '
                .'BEFORE INSERT ON `users` FOR EACH ROW SET NEW.`name` = NEW.`name`'
            );

            try {
                $migration->up();
            } catch (RuntimeException $exception) {
                $caught = $exception;
            }

            $this->assertNotNull($caught);
            $this->assertStringContainsString(
                self::INSERT_TRIGGER,
                $caught->getMessage()
            );
        } finally {
            if ($this->findTrigger(self::INSERT_TRIGGER) !== null) {
                DB::unprepared('DROP TRIGGER `'.self::INSERT_TRIGGER.'`');
            }

            $migration->up();
        }
    }

    public function test_up_rejects_all_invalid_preexisting_data_states(): void
    {
        $this->assertInvalidStateIsRejected('orphan');
        $this->assertInvalidStateIsRejected('cross');
        $this->assertInvalidStateIsRejected('duplicate');
        $this->assertInvalidStateIsRejected('global');
    }

    private function assertInvalidStateIsRejected(string $state): void
    {
        $migration = $this->migration();
        $tenantA = $this->createTenant("state-{$state}-a");
        $tenantB = $this->createTenant("state-{$state}-b");
        $insertedIds = [];
        $caught = null;

        try {
            $migration->down();

            if ($state === 'orphan') {
                $insertedIds[] = $this->insertUser(
                    $tenantA['company_id'],
                    999999999,
                    'customer',
                    "state-{$state}"
                );
            } elseif ($state === 'cross') {
                $insertedIds[] = $this->insertUser(
                    $tenantA['company_id'],
                    $tenantB['customer_id'],
                    'customer',
                    "state-{$state}"
                );
            } elseif ($state === 'duplicate') {
                $insertedIds[] = $this->insertUser(
                    $tenantA['company_id'],
                    $tenantA['customer_id'],
                    'customer',
                    "state-{$state}-1"
                );
                $insertedIds[] = $this->insertUser(
                    $tenantA['company_id'],
                    $tenantA['customer_id'],
                    'customer',
                    "state-{$state}-2"
                );
            } else {
                $insertedIds[] = $this->insertUser(
                    null,
                    $tenantA['customer_id'],
                    'customer',
                    "state-{$state}"
                );
            }

            try {
                $migration->up();
            } catch (RuntimeException $exception) {
                $caught = $exception;
            }

            $this->assertNotNull($caught, "State {$state} was not rejected.");
        } finally {
            DB::table('users')->whereIn('id', $insertedIds)->delete();
            $migration->up();
        }
    }

    private function createTenant(string $suffix): array
    {
        $now = now();
        $companyId = DB::table('companies')->insertGetId([
            'name' => "Company {$suffix}",
            'slug' => "company-{$suffix}-".uniqid(),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $customerId = DB::table('customers')->insertGetId([
            'company_id' => $companyId,
            'first_name' => 'Customer',
            'last_name' => $suffix,
            'status' => 'active',
            'score' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'company_id' => $companyId,
            'customer_id' => $customerId,
        ];
    }

    private function insertUser(
        ?int $companyId,
        ?int $customerId,
        string $role,
        string $suffix
    ): int {
        return DB::table('users')->insertGetId([
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'name' => "User {$suffix}",
            'email' => "{$suffix}-".uniqid().'@example.test',
            'password' => bcrypt('password'),
            'role' => $role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function migration(): object
    {
        return require database_path(
            'migrations/2026_07_27_000100_harden_users_customer_id.php'
        );
    }

    private function index(string $name): array
    {
        $index = $this->findIndex($name);
        $this->assertNotNull($index, "Index {$name} was not found.");

        return $index;
    }

    private function findIndex(string $name): ?array
    {
        foreach (Schema::getIndexes('users') as $index) {
            if (strcasecmp((string) $index['name'], $name) === 0) {
                return $index;
            }
        }

        return null;
    }

    private function foreignKey(string $name): array
    {
        $foreignKey = $this->findForeignKey($name);
        $this->assertNotNull($foreignKey, "Foreign key {$name} was not found.");

        return $foreignKey;
    }

    private function findForeignKey(string $name): ?array
    {
        foreach (Schema::getForeignKeys('users') as $foreignKey) {
            if (strcasecmp((string) $foreignKey['name'], $name) === 0) {
                return $foreignKey;
            }
        }

        return null;
    }

    private function trigger(string $name): object
    {
        $trigger = $this->findTrigger($name);
        $this->assertNotNull($trigger, "Trigger {$name} was not found.");

        return $trigger;
    }

    private function findTrigger(string $name): ?object
    {
        return DB::table('information_schema.TRIGGERS')
            ->whereRaw('TRIGGER_SCHEMA = DATABASE()')
            ->whereRaw('LOWER(TRIGGER_NAME) = ?', [strtolower($name)])
            ->first([
                'TRIGGER_NAME',
                'EVENT_MANIPULATION',
                'ACTION_TIMING',
                'EVENT_OBJECT_TABLE',
                'ACTION_STATEMENT',
            ]);
    }

    private function assertTrigger(object $trigger, string $event): void
    {
        $this->assertSame($event, strtoupper($trigger->EVENT_MANIPULATION));
        $this->assertSame('BEFORE', strtoupper($trigger->ACTION_TIMING));
        $this->assertSame('users', strtolower($trigger->EVENT_OBJECT_TABLE));

        $statement = $this->canonicalSql($trigger->ACTION_STATEMENT);

        $this->assertStringContainsString(
            'new.customer_idisnotnullandnew.company_idisnull',
            $statement
        );
        $this->assertStringContainsString("signalsqlstate'45000'", $statement);
        $this->assertStringContainsString(
            'auserlinkedtoacustomermustbelongtoacompany',
            $statement
        );
    }

    private function assertForeignKey(
        array $foreignKey,
        array $columns,
        string $foreignTable,
        array $foreignColumns
    ): void {
        $this->assertSame($columns, $foreignKey['columns']);
        $this->assertSame($foreignTable, strtolower($foreignKey['foreign_table']));
        $this->assertSame($foreignColumns, $foreignKey['foreign_columns']);
        $this->assertContains(
            strtoupper($foreignKey['on_update']),
            ['RESTRICT', 'NO ACTION']
        );
        $this->assertContains(
            strtoupper($foreignKey['on_delete']),
            ['RESTRICT', 'NO ACTION']
        );
    }

    private function assertIndexVisible(string $name): void
    {
        $visibility = DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'users')
            ->whereRaw('LOWER(INDEX_NAME) = ?', [strtolower($name)])
            ->value('IS_VISIBLE');

        $this->assertSame('YES', strtoupper((string) $visibility));
    }

    private function canonicalSql(string $sql): string
    {
        return preg_replace('/\s+/', '', strtolower(str_replace('`', '', $sql))) ?? '';
    }

    private function column(string $table, string $column): object
    {
        $definition = DB::table('information_schema.COLUMNS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->first(['COLUMN_TYPE', 'IS_NULLABLE', 'COLUMN_DEFAULT']);

        $this->assertNotNull($definition);

        return $definition;
    }

    private function assertQueryFails(callable $callback): void
    {
        try {
            $callback();
            $this->fail('The database accepted an invalid users.customer_id state.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }
    }

    private function users(): array
    {
        return DB::table('users')
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();
    }

    private function otherIndexes(): array
    {
        return array_values(array_filter(
            Schema::getIndexes('users'),
            fn (array $index): bool => ! in_array(
                strtolower((string) $index['name']),
                [strtolower(self::CUSTOMER_UNIQUE), strtolower(self::TENANT_INDEX)],
                true
            )
        ));
    }

    private function otherForeignKeys(): array
    {
        return array_values(array_filter(
            Schema::getForeignKeys('users'),
            fn (array $foreignKey): bool => ! in_array(
                strtolower((string) $foreignKey['name']),
                [strtolower(self::CUSTOMER_FOREIGN), strtolower(self::TENANT_FOREIGN)],
                true
            )
        ));
    }

    private function namedIndexCount(string $name): int
    {
        return (int) DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'users')
            ->whereRaw('LOWER(INDEX_NAME) = ?', [strtolower($name)])
            ->distinct()
            ->count('INDEX_NAME');
    }

    private function namedForeignKeyCount(string $name): int
    {
        return (int) DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->whereRaw('CONSTRAINT_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'users')
            ->whereRaw('LOWER(CONSTRAINT_NAME) = ?', [strtolower($name)])
            ->count();
    }

    private function namedTriggerCount(string $name): int
    {
        return (int) DB::table('information_schema.TRIGGERS')
            ->whereRaw('TRIGGER_SCHEMA = DATABASE()')
            ->whereRaw('LOWER(TRIGGER_NAME) = ?', [strtolower($name)])
            ->count();
    }
}
