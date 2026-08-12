# Safe Testing Environment

## Requirements

- PHP 8.3 or newer with `pdo_mysql`.
- MySQL 8 with an isolated `melpres_test` database.
- A limited database user that has privileges only on `melpres_test`.
- Composer development dependencies installed.

Never run the test suite against `melpres_db`. The PHPUnit bootstrap and base
test case both reject any database whose effective name is not exactly
`melpres_test`.

## Create The Database Manually

Run the following SQL with an administrative MySQL account:

```sql
CREATE DATABASE melpres_test
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

CREATE USER 'melpres_test_user'@'localhost'
IDENTIFIED BY 'CHANGE_THIS_PASSWORD';

GRANT ALL PRIVILEGES ON melpres_test.*
TO 'melpres_test_user'@'localhost';

FLUSH PRIVILEGES;
```

On Windows, a connection to `127.0.0.1` may require a separate
`'melpres_test_user'@'127.0.0.1'` account with the same limited grant.

## Allow Test Triggers With Binary Logging

The tenant integrity migrations create MySQL triggers. When binary logging is
enabled and `log_bin_trust_function_creators` is disabled, MySQL raises error
1419 for the limited test user.

On the local development/testing MySQL server only, run this with an
administrative account:

```sql
SET GLOBAL log_bin_trust_function_creators = 1;
```

Verify the result:

```sql
SHOW VARIABLES LIKE 'log_bin_trust_function_creators';
```

The value must be `ON` or `1`. This global setting may reset after MySQL
restarts. If persistence is required, configure
`log_bin_trust_function_creators=1` in the local `my.ini` or `my.cnf`.

Do not grant `SUPER` to `melpres_test_user`. Do not run migration or cleanup
commands against `melpres_db`.

## Configure Laravel

Copy the example without putting real credentials in Git:

```powershell
Copy-Item .env.testing.example .env.testing
```

Set `DB_PASSWORD` in `.env.testing`, then generate an isolated application key:

```powershell
php artisan key:generate --env=testing
```

Install the versions locked by `composer.lock`:

```powershell
composer install
```

Do not use `composer update` for environment setup.

## Verify The Connection

This read-only command must report `melpres_test`:

```powershell
php artisan db:show --env=testing
```

Stop immediately if any command reports `melpres_db`.

## Run Tests

Run the pure, non-database guard test first:

```powershell
vendor/bin/phpunit tests/Unit/TestingDatabaseGuardTest.php
```

Then run one feature file. The global guard validates the connected database
before `RefreshDatabase` or `DatabaseMigrations` starts:

```powershell
vendor/bin/phpunit tests/Feature/CustomerCreationTest.php
```

The complete suite currently passes against the isolated MySQL `melpres_test`
database: 249 tests, 1123 assertions, 0 errors, and 0 failures.
`TestingDatabaseGuard` prevents the suite from running against `melpres_db`.
