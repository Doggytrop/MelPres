<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionObject;
use RuntimeException;
use Tests\TestCase;

class ActivityLogTenantGuardTriggersTest extends TestCase
{
    private ?string $logsTable = null;

    private ?string $usersTable = null;

    private ?string $insertTrigger = null;

    private ?string $updateTrigger = null;

    private ?string $otherTrigger = null;

    private ?string $foreignKey = null;

    private ?string $tenantIndex = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('This test requires MySQL trigger semantics.');
        }

        if (! str_contains(strtolower((string) DB::connection()->getDatabaseName()), 'test')) {
            $this->fail('Refusing to create triggers outside a MySQL test database.');
        }

        $suffix = substr(md5(static::class.$this->name()), 0, 10);
        $this->logsTable = "tg_logs_{$suffix}";
        $this->usersTable = "tg_users_{$suffix}";
        $this->insertTrigger = "tg_bi_{$suffix}";
        $this->updateTrigger = "tg_bu_{$suffix}";
        $this->otherTrigger = "tg_other_{$suffix}";
        $this->foreignKey = "tg_user_fk_{$suffix}";
        $this->tenantIndex = "tg_company_user_idx_{$suffix}";

        $this->dropTriggers();
        Schema::dropIfExists($this->logsTable);
        Schema::dropIfExists($this->usersTable);

        Schema::create($this->usersTable, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
            $table->unique(['company_id', 'id']);
        });

        Schema::create($this->logsTable, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'user_id'], $this->tenantIndex);
            $table->foreign('user_id', $this->foreignKey)
                ->references('id')
                ->on($this->usersTable)
                ->noActionOnUpdate()
                ->nullOnDelete();
        });

        DB::table($this->usersTable)->insert([
            ['id' => 1, 'company_id' => 1, 'name' => 'User One'],
            ['id' => 2, 'company_id' => 2, 'name' => 'User Two'],
        ]);
    }

    protected function tearDown(): void
    {
        $this->dropTriggers();

        if ($this->logsTable !== null) {
            Schema::dropIfExists($this->logsTable);
        }

        if ($this->usersTable !== null) {
            Schema::dropIfExists($this->usersTable);
        }

        parent::tearDown();
    }

    public function test_insert_guard_accepts_valid_and_system_logs_and_rejects_invalid_actors(): void
    {
        $this->migration()->up();

        $validId = $this->insertLog(1, 1, 'User One');
        $systemId = $this->insertLog(null, null, 'System');

        $this->assertNotNull($validId);
        $this->assertNotNull($systemId);

        $this->assertQueryRejected(
            fn () => $this->insertLog(1, 2, 'User Two'),
            'activity_logs.user_id must belong to activity_logs.company_id'
        );
        $this->assertQueryRejected(
            fn () => $this->insertLog(1, 999, 'Missing User'),
            'activity_logs.user_id must belong to activity_logs.company_id'
        );
        $this->assertQueryRejected(
            fn () => $this->insertLog(null, 1, 'User One'),
            'activity_logs.company_id is required when user_id is present'
        );
    }

    public function test_update_guard_validates_the_complete_new_state(): void
    {
        $this->migration()->up();
        $logId = $this->insertLog(1, 1, 'User One');

        $this->assertQueryRejected(
            fn () => DB::table($this->logsTable)->where('id', $logId)
                ->update(['user_id' => 2]),
            'activity_logs.user_id must belong to activity_logs.company_id'
        );
        $this->assertQueryRejected(
            fn () => DB::table($this->logsTable)->where('id', $logId)
                ->update(['company_id' => 2]),
            'activity_logs.user_id must belong to activity_logs.company_id'
        );

        $this->assertSame(
            1,
            DB::table($this->logsTable)->where('id', $logId)
                ->update(['description' => 'Allowed update'])
        );
    }

    public function test_update_of_an_inconsistent_row_is_rejected_after_guard_is_restored(): void
    {
        $migration = $this->migration();
        $migration->up();
        $migration->down();

        $logId = $this->insertLog(1, 2, 'User Two');
        $this->createExpectedTriggerDirectly($migration, $this->updateTrigger);

        $this->assertQueryRejected(
            fn () => DB::table($this->logsTable)->where('id', $logId)
                ->update(['description' => 'Should fail']),
            'activity_logs.user_id must belong to activity_logs.company_id'
        );
    }

    public function test_user_delete_keeps_company_and_snapshot_but_sets_user_id_null(): void
    {
        $this->migration()->up();
        $logId = $this->insertLog(1, 1, 'Historical User');

        DB::table($this->usersTable)->where('id', 1)->delete();

        $log = DB::table($this->logsTable)->where('id', $logId)->first();

        $this->assertNull($log->user_id);
        $this->assertSame(1, $log->company_id);
        $this->assertSame('Historical User', $log->user_name);
    }

    public function test_up_and_down_are_idempotent_and_preserve_fk_index_and_other_trigger(): void
    {
        DB::unprepared(
            "CREATE TRIGGER `{$this->otherTrigger}` "
            ."BEFORE INSERT ON `{$this->logsTable}` "
            .'FOR EACH ROW SET NEW.`user_name` = NEW.`user_name`'
        );

        $migration = $this->migration();
        $migration->up();
        $migration->up();

        $this->assertSame(1, $this->triggerCount($this->insertTrigger));
        $this->assertSame(1, $this->triggerCount($this->updateTrigger));
        $this->assertSame(1, $this->triggerCount($this->otherTrigger));
        $this->assertForeignKeyAndIndexRemain();

        $migration->down();
        $migration->down();

        $this->assertSame(0, $this->triggerCount($this->insertTrigger));
        $this->assertSame(0, $this->triggerCount($this->updateTrigger));
        $this->assertSame(1, $this->triggerCount($this->otherTrigger));
        $this->assertForeignKeyAndIndexRemain();
    }

    public function test_up_rejects_existing_cross_company_data(): void
    {
        $this->insertLog(1, 2, 'User Two');

        try {
            $this->migration()->up();
            $this->fail('up() accepted existing cross-company activity log data.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'user_id no pertenece a company_id',
                $exception->getMessage()
            );
        }

        $this->assertSame(0, $this->triggerCount($this->insertTrigger));
        $this->assertSame(0, $this->triggerCount($this->updateTrigger));
    }

    public function test_up_and_down_reject_a_target_trigger_with_another_definition(): void
    {
        DB::unprepared(
            "CREATE TRIGGER `{$this->insertTrigger}` "
            ."BEFORE INSERT ON `{$this->logsTable}` "
            .'FOR EACH ROW SET NEW.`user_name` = NEW.`user_name`'
        );

        foreach (['up', 'down'] as $method) {
            try {
                $this->migration()->{$method}();
                $this->fail("{$method}() accepted a conflicting target trigger.");
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString(
                    'existe con una definición distinta',
                    $exception->getMessage()
                );
            }
        }

        $this->assertSame(1, $this->triggerCount($this->insertTrigger));
        $this->assertSame(0, $this->triggerCount($this->updateTrigger));
    }

    private function migration(): object
    {
        $migration = require database_path(
            'migrations/2026_07_27_000030_add_activity_log_tenant_guard_triggers.php'
        );
        $reflection = new ReflectionObject($migration);

        foreach ([
            'activityLogsTable' => $this->logsTable,
            'usersTable' => $this->usersTable,
            'insertTrigger' => $this->insertTrigger,
            'updateTrigger' => $this->updateTrigger,
            'userForeignKey' => $this->foreignKey,
        ] as $propertyName => $value) {
            $reflection->getProperty($propertyName)->setValue($migration, $value);
        }

        return $migration;
    }

    private function createExpectedTriggerDirectly(object $migration, string $name): void
    {
        $method = (new ReflectionObject($migration))->getMethod('expectedTriggers');
        $definitions = $method->invoke($migration);

        DB::unprepared($definitions[$name]['create_sql']);
    }

    private function insertLog(?int $companyId, ?int $userId, string $userName): int
    {
        return DB::table($this->logsTable)->insertGetId([
            'company_id' => $companyId,
            'user_id' => $userId,
            'user_name' => $userName,
            'description' => 'Test log',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assertQueryRejected(callable $callback, string $message): void
    {
        try {
            $callback();
            $this->fail('The trigger accepted an invalid SQL operation.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());
        }
    }

    private function triggerCount(string $name): int
    {
        return DB::table('information_schema.TRIGGERS')
            ->whereRaw('TRIGGER_SCHEMA = DATABASE()')
            ->where('TRIGGER_NAME', $name)
            ->count();
    }

    private function assertForeignKeyAndIndexRemain(): void
    {
        $foreignKey = collect(Schema::getForeignKeys($this->logsTable))
            ->firstWhere('name', $this->foreignKey);

        $this->assertNotNull($foreignKey);
        $this->assertSame(['user_id'], $foreignKey['columns']);
        $this->assertSame('SET NULL', strtoupper($foreignKey['on_delete']));
        $this->assertContains(
            strtoupper($foreignKey['on_update']),
            ['NO ACTION', 'RESTRICT']
        );

        $index = collect(Schema::getIndexes($this->logsTable))
            ->firstWhere('name', $this->tenantIndex);

        $this->assertNotNull($index);
        $this->assertSame(['company_id', 'user_id'], $index['columns']);
    }

    private function dropTriggers(): void
    {
        foreach ([$this->insertTrigger, $this->updateTrigger, $this->otherTrigger] as $trigger) {
            if ($trigger !== null) {
                DB::unprepared("DROP TRIGGER IF EXISTS `{$trigger}`");
            }
        }
    }
}
