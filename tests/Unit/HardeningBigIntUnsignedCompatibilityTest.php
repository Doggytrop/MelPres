<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class HardeningBigIntUnsignedCompatibilityTest extends TestCase
{
    private const MIGRATIONS = [
        '2026_07_27_000050_harden_customers_company_id.php',
        '2026_07_27_000060_harden_loans_company_id.php',
        '2026_07_27_000070_harden_payments_company_id.php',
        '2026_07_27_000080_harden_customer_documents_company_id.php',
        '2026_07_27_000090_harden_restructurings_company_id.php',
        '2026_07_27_000100_harden_users_customer_id.php',
    ];

    public function test_bigint_unsigned_without_display_width_is_accepted(): void
    {
        $this->assertAcrossMigrations(true, 'bigint', 'bigint unsigned');
    }

    public function test_bigint_unsigned_with_display_width_is_accepted(): void
    {
        $this->assertAcrossMigrations(true, 'bigint', 'bigint(20) unsigned');
    }

    public function test_uppercase_bigint_unsigned_is_accepted(): void
    {
        $this->assertAcrossMigrations(true, 'BIGINT', 'BIGINT(20) UNSIGNED');
    }

    public function test_bigint_without_unsigned_is_rejected(): void
    {
        $this->assertAcrossMigrations(false, 'bigint', 'bigint');
    }

    public function test_unsigned_int_is_rejected(): void
    {
        $this->assertAcrossMigrations(false, 'int', 'int unsigned');
        $this->assertAcrossMigrations(false, 'int', 'int(10) unsigned');
    }

    public function test_decimal_and_varchar_are_rejected(): void
    {
        $this->assertAcrossMigrations(false, 'decimal', 'decimal(20,0) unsigned');
        $this->assertAcrossMigrations(false, 'varchar', 'varchar(255)');
    }

    private function assertAcrossMigrations(
        bool $expected,
        string $dataType,
        string $columnType
    ): void {
        foreach (self::MIGRATIONS as $filename) {
            $migration = require dirname(__DIR__, 2).'/database/migrations/'.$filename;
            $method = new ReflectionMethod($migration, 'isBigIntUnsigned');
            $column = str_contains($filename, '_000100_')
                ? (object) ['DATA_TYPE' => $dataType, 'COLUMN_TYPE' => $columnType]
                : ['type_name' => $dataType, 'type' => $columnType];

            $this->assertSame(
                $expected,
                $method->invoke($migration, $column),
                $filename.' rejected/accepted an unexpected SQL type.'
            );
        }
    }
}
