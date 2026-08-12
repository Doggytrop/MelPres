<?php

namespace Tests\Support;

use RuntimeException;

final class TestingDatabaseGuard
{
    private const SAFE_ENVIRONMENT = 'testing';

    private const SAFE_CONNECTION = 'mysql';

    private const SAFE_DATABASE = 'melpres_test';

    public static function assertSafe(
        string $environment,
        string $defaultConnection,
        ?string $configuredDatabase,
        ?string $actualDatabase
    ): void {
        self::assertConfiguration(
            $environment,
            $defaultConnection,
            $configuredDatabase
        );
        self::assertActualDatabase($actualDatabase);
    }

    public static function assertConfiguration(
        string $environment,
        string $defaultConnection,
        ?string $configuredDatabase
    ): void {
        if ($environment !== self::SAFE_ENVIRONMENT) {
            throw new RuntimeException(
                "Unsafe test environment [{$environment}]. Expected exactly [testing]."
            );
        }

        if ($defaultConnection !== self::SAFE_CONNECTION) {
            throw new RuntimeException(
                "Unsafe test database connection [{$defaultConnection}]. "
                .'Expected exactly [mysql]; SQLite and other drivers are forbidden.'
            );
        }

        self::assertDatabaseName($configuredDatabase, 'configured');
    }

    public static function assertActualDatabase(?string $actualDatabase): void
    {
        self::assertDatabaseName($actualDatabase, 'connected');
    }

    private static function assertDatabaseName(
        ?string $database,
        string $source
    ): void {
        if ($database === null || $database === '') {
            throw new RuntimeException(
                "Unsafe {$source} test database: the database name is empty."
            );
        }

        if (str_contains(strtolower($database), 'melpres_db')) {
            throw new RuntimeException(
                "Unsafe {$source} test database [{$database}]: "
                .'melpres_db must never be used by PHPUnit.'
            );
        }

        if ($database !== self::SAFE_DATABASE) {
            throw new RuntimeException(
                "Unsafe {$source} test database [{$database}]. "
                .'Expected exactly [melpres_test].'
            );
        }
    }
}
