<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\TestingDatabaseGuard;

class TestingDatabaseGuardTest extends TestCase
{
    public function test_it_accepts_only_the_exact_safe_configuration(): void
    {
        TestingDatabaseGuard::assertSafe(
            'testing',
            'mysql',
            'melpres_test',
            'melpres_test'
        );

        $this->addToAssertionCount(1);
    }

    public function test_it_rejects_production(): void
    {
        $this->assertRejected('production', 'mysql', 'melpres_test', 'melpres_test');
    }

    public function test_it_rejects_local(): void
    {
        $this->assertRejected('local', 'mysql', 'melpres_test', 'melpres_test');
    }

    public function test_it_rejects_sqlite(): void
    {
        $this->assertRejected('testing', 'sqlite', 'melpres_test', 'melpres_test');
    }

    public function test_it_rejects_the_development_database(): void
    {
        $this->assertRejected('testing', 'mysql', 'melpres_db', 'melpres_db');
    }

    public function test_it_rejects_any_other_database(): void
    {
        $this->assertRejected('testing', 'mysql', 'another_test', 'another_test');
    }

    public function test_it_rejects_an_empty_database_name(): void
    {
        $this->assertRejected('testing', 'mysql', '', '');
    }

    public function test_it_rejects_case_variations(): void
    {
        $this->assertRejected('testing', 'mysql', 'MelPres_Test', 'MelPres_Test');
    }

    public function test_it_rejects_when_the_connected_database_differs(): void
    {
        $this->assertRejected('testing', 'mysql', 'melpres_test', 'melpres_db');
    }

    private function assertRejected(
        string $environment,
        string $connection,
        ?string $configuredDatabase,
        ?string $actualDatabase
    ): void {
        $this->expectException(RuntimeException::class);

        TestingDatabaseGuard::assertSafe(
            $environment,
            $connection,
            $configuredDatabase,
            $actualDatabase
        );
    }
}
