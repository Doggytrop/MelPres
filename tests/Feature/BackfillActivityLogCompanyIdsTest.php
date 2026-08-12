<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionObject;
use RuntimeException;
use Tests\TestCase;

class BackfillActivityLogCompanyIdsTest extends TestCase
{
    private const LEGACY_IDS = [
        1, 7, 10, 12, 13, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25,
    ];

    private ?string $logsTable = null;

    private ?string $usersTable = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('This test requires MySQL.');
        }

        if (! str_contains(strtolower((string) DB::connection()->getDatabaseName()), 'test')) {
            $this->fail('Refusing to create test tables outside a MySQL test database.');
        }

        $suffix = substr(md5(static::class.$this->name()), 0, 10);
        $this->logsTable = "bf_activity_logs_{$suffix}";
        $this->usersTable = "bf_users_{$suffix}";

        Schema::dropIfExists($this->logsTable);
        Schema::dropIfExists($this->usersTable);

        Schema::create($this->usersTable, function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
        });

        Schema::create($this->logsTable, function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('action');
            $table->string('module');
            $table->string('description');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        $this->seedLegacyRows();
    }

    protected function tearDown(): void
    {
        if ($this->logsTable !== null) {
            Schema::dropIfExists($this->logsTable);
        }

        if ($this->usersTable !== null) {
            Schema::dropIfExists($this->usersTable);
        }

        parent::tearDown();
    }

    public function test_backfill_is_exact_idempotent_and_down_only_reverts_legacy_rows(): void
    {
        DB::table($this->logsTable)->insert([
            'id' => 99,
            'company_id' => 1,
            'user_id' => 1,
            'user_name' => 'Administrador',
            'action' => 'update',
            'module' => 'settings',
            'description' => 'Unrelated row',
            'ip_address' => '10.0.0.1',
            'created_at' => '2026-07-27 20:00:00',
            'updated_at' => '2026-07-27 20:00:00',
        ]);

        $before = $this->rowsWithoutCompanyId();
        $migration = $this->migration();

        $migration->up();
        $migration->up();

        $this->assertSame(0, DB::table($this->logsTable)->whereNull('company_id')->count());
        $this->assertSame(
            9,
            DB::table($this->logsTable)->whereIn('id', self::LEGACY_IDS)
                ->where('company_id', 1)->count()
        );
        $this->assertSame(
            6,
            DB::table($this->logsTable)->whereIn('id', self::LEGACY_IDS)
                ->where('company_id', 2)->count()
        );
        $this->assertSame($before, $this->rowsWithoutCompanyId());
        $this->assertSame(1, DB::table($this->logsTable)->where('id', 99)->value('company_id'));

        $migration->down();

        $this->assertSame(
            15,
            DB::table($this->logsTable)
                ->whereIn('id', self::LEGACY_IDS)
                ->whereNull('company_id')
                ->count()
        );
        $this->assertSame(1, DB::table($this->logsTable)->where('id', 99)->value('company_id'));
        $this->assertSame($before, $this->rowsWithoutCompanyId());
    }

    public function test_up_aborts_if_an_expected_log_is_missing(): void
    {
        DB::table($this->logsTable)->where('id', 25)->delete();

        $this->assertUpFailsWithoutChangingRows('Faltan activity_logs legacy esperados');
    }

    public function test_up_and_down_do_nothing_on_a_clean_database(): void
    {
        DB::table($this->logsTable)->whereIn('id', self::LEGACY_IDS)->delete();
        $before = DB::table($this->logsTable)->get()->all();
        $migration = $this->migration();

        $migration->up();
        $this->assertEquals($before, DB::table($this->logsTable)->get()->all());

        $migration->down();
        $this->assertEquals($before, DB::table($this->logsTable)->get()->all());
    }

    public function test_down_aborts_if_only_some_expected_logs_exist(): void
    {
        DB::table($this->logsTable)->where('id', 25)->delete();
        $before = DB::table($this->logsTable)->orderBy('id')->get()->all();

        try {
            $this->migration()->down();
            $this->fail('down() completed with only part of the expected legacy IDs.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'Faltan activity_logs legacy esperados',
                $exception->getMessage()
            );
        }

        $this->assertEquals($before, DB::table($this->logsTable)->orderBy('id')->get()->all());
    }

    public function test_up_aborts_if_no_expected_ids_exist_but_another_null_log_does(): void
    {
        DB::table($this->logsTable)->whereIn('id', self::LEGACY_IDS)->delete();
        DB::table($this->logsTable)->insert([
            'id' => 99,
            'company_id' => null,
            'user_id' => 1,
            'user_name' => 'Administrador',
            'action' => 'login',
            'module' => 'auth',
            'description' => 'Inició sesión',
            'ip_address' => '127.0.0.1',
            'created_at' => '2026-07-27 20:00:00',
            'updated_at' => '2026-07-27 20:00:00',
        ]);

        $this->assertUpFailsWithoutChangingRows('no contemplados: 99');
    }

    public function test_up_aborts_if_user_does_not_exist(): void
    {
        DB::table($this->usersTable)->where('id', 3)->delete();

        $this->assertUpFailsWithoutChangingRows('No existe el usuario');
    }

    public function test_up_aborts_if_user_company_is_null(): void
    {
        DB::table($this->usersTable)->where('id', 3)->update(['company_id' => null]);

        $this->assertUpFailsWithoutChangingRows('no tiene company_id');
    }

    public function test_up_aborts_if_user_name_does_not_match(): void
    {
        DB::table($this->logsTable)->where('id', 7)->update(['user_name' => 'Otro nombre']);

        $this->assertUpFailsWithoutChangingRows('user_name no coincide');
    }

    public function test_up_aborts_if_log_is_not_the_expected_auth_login(): void
    {
        DB::table($this->logsTable)->where('id', 1)->update(['module' => 'users']);

        $this->assertUpFailsWithoutChangingRows('no conserva la estructura legacy esperada');
    }

    public function test_up_aborts_if_an_unlisted_null_company_log_exists(): void
    {
        DB::table($this->logsTable)->insert([
            'id' => 99,
            'company_id' => null,
            'user_id' => 1,
            'user_name' => 'Administrador',
            'action' => 'login',
            'module' => 'auth',
            'description' => 'Inició sesión',
            'ip_address' => '127.0.0.1',
            'created_at' => '2026-07-27 20:00:00',
            'updated_at' => '2026-07-27 20:00:00',
        ]);

        $this->assertUpFailsWithoutChangingRows('no contemplados: 99');
    }

    public function test_up_aborts_if_existing_company_does_not_match_user(): void
    {
        DB::table($this->logsTable)->where('id', 1)->update(['company_id' => 2]);

        $this->assertUpFailsWithoutChangingRows('company_id no coincide');
    }

    public function test_post_validation_failure_rolls_back_all_updates(): void
    {
        DB::table($this->usersTable)->where('id', 3)->update(['company_id' => 3]);

        $this->assertUpFailsWithoutChangingRows('distribución final no coincide');
    }

    public function test_down_aborts_if_a_backfilled_log_changed_functionally(): void
    {
        $migration = $this->migration();
        $migration->up();

        DB::table($this->logsTable)->where('id', 1)->update(['action' => 'update']);

        try {
            $migration->down();
            $this->fail('down() did not reject a functionally changed log.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'no conserva la estructura legacy esperada',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            15,
            DB::table($this->logsTable)
                ->whereIn('id', self::LEGACY_IDS)
                ->whereNotNull('company_id')
                ->count()
        );
    }

    private function seedLegacyRows(): void
    {
        DB::table($this->usersTable)->insert([
            ['id' => 1, 'company_id' => 1, 'name' => 'Administrador'],
            ['id' => 3, 'company_id' => 2, 'name' => 'Administrador Empresa 2'],
        ]);

        $companyOneIds = [1, 10, 13, 17, 19, 21, 23, 24, 25];

        foreach (self::LEGACY_IDS as $id) {
            $isCompanyOne = in_array($id, $companyOneIds, true);

            DB::table($this->logsTable)->insert([
                'id' => $id,
                'company_id' => null,
                'user_id' => $isCompanyOne ? 1 : 3,
                'user_name' => $isCompanyOne
                    ? 'Administrador'
                    : 'Administrador Empresa 2',
                'action' => 'login',
                'module' => 'auth',
                'description' => 'Inició sesión',
                'model_type' => null,
                'model_id' => null,
                'old_values' => null,
                'new_values' => null,
                'ip_address' => '127.0.0.1',
                'created_at' => "2026-07-27 16:{$id}:00",
                'updated_at' => "2026-07-27 16:{$id}:00",
            ]);
        }
    }

    private function migration(): object
    {
        $migration = require database_path(
            'migrations/2026_07_27_000020_backfill_activity_log_company_ids.php'
        );
        $reflection = new ReflectionObject($migration);

        foreach ([
            'activityLogsTable' => $this->logsTable,
            'usersTable' => $this->usersTable,
        ] as $propertyName => $value) {
            $property = $reflection->getProperty($propertyName);
            $property->setValue($migration, $value);
        }

        return $migration;
    }

    private function rowsWithoutCompanyId(): array
    {
        return DB::table($this->logsTable)
            ->orderBy('id')
            ->get()
            ->mapWithKeys(function (object $row): array {
                $attributes = (array) $row;
                unset($attributes['company_id']);
                ksort($attributes);

                return [(int) $row->id => $attributes];
            })
            ->all();
    }

    private function assertUpFailsWithoutChangingRows(string $message): void
    {
        $before = DB::table($this->logsTable)->orderBy('id')->get()->map(
            fn (object $row): array => (array) $row
        )->all();

        try {
            $this->migration()->up();
            $this->fail('up() completed despite invalid legacy data.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());
        }

        $after = DB::table($this->logsTable)->orderBy('id')->get()->map(
            fn (object $row): array => (array) $row
        )->all();

        $this->assertSame($before, $after);
    }
}
