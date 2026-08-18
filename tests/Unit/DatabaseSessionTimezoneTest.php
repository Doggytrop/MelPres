<?php

namespace Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSessionTimezoneTest extends TestCase
{
    public function test_mysql_and_mariadb_connections_receive_the_current_application_offset(): void
    {
        $offset = CarbonImmutable::now(config('app.timezone'))->format('P');

        $this->assertSame($offset, config('database.connections.mysql.timezone'));
        $this->assertSame($offset, config('database.connections.mariadb.timezone'));
    }

    public function test_database_session_uses_the_same_local_day_as_laravel(): void
    {
        $timezone = config('app.timezone');
        $expectedOffset = CarbonImmutable::now($timezone)->format('P');

        $row = DB::selectOne('SELECT NOW() AS db_now, CURRENT_DATE AS db_today, @@session.time_zone AS session_tz');

        $this->assertSame($expectedOffset, $row->session_tz);
        $this->assertSame(now($timezone)->toDateString(), (string) $row->db_today);

        $databaseNow = CarbonImmutable::parse($row->db_now, $timezone);
        $this->assertLessThanOrEqual(2, $databaseNow->diffInSeconds(CarbonImmutable::now($timezone)));
    }
}
