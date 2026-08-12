<?php

use Dotenv\Dotenv;
use Tests\Support\TestingDatabaseGuard;

$basePath = dirname(__DIR__, 2);

require $basePath.'/vendor/autoload.php';

if (! is_file($basePath.'/.env.testing')) {
    throw new \RuntimeException(
        'Missing .env.testing. PHPUnit cannot start without the isolated testing environment.'
    );
}

Dotenv::createImmutable($basePath, '.env.testing')->load();

TestingDatabaseGuard::assertConfiguration(
    (string) ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? getenv('APP_ENV') ?: ''),
    (string) ($_ENV['DB_CONNECTION'] ?? $_SERVER['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: ''),
    (string) ($_ENV['DB_DATABASE'] ?? $_SERVER['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: '')
);
