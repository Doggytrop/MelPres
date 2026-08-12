<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;
use Tests\Support\TestingDatabaseGuard;
use Throwable;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $environment = (string) $app->environment();
        $defaultConnection = (string) $app['config']->get('database.default');
        $configuredDatabase = $app['config']->get(
            "database.connections.{$defaultConnection}.database"
        );

        TestingDatabaseGuard::assertConfiguration(
            $environment,
            $defaultConnection,
            is_string($configuredDatabase) ? $configuredDatabase : null
        );

        try {
            $actualDatabase = $app['db']
                ->connection($defaultConnection)
                ->getDatabaseName();
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Testing database safety check could not connect to melpres_test. '
                .'No migrations or cleanup operations were started.',
                previous: $exception
            );
        }

        TestingDatabaseGuard::assertActualDatabase(
            is_string($actualDatabase) ? $actualDatabase : null
        );

        return $app;
    }
}
