<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class HardenSettingsCompanyIdColumnTypeTest extends TestCase
{
    public function test_bigint_unsigned_without_display_width_is_accepted(): void
    {
        $this->assertTrue($this->isBigIntUnsigned('bigint', 'bigint unsigned'));
    }

    public function test_bigint_unsigned_with_display_width_is_accepted(): void
    {
        $this->assertTrue($this->isBigIntUnsigned('bigint', 'bigint(20) unsigned'));
    }

    public function test_case_variants_and_other_display_widths_are_accepted(): void
    {
        $this->assertTrue($this->isBigIntUnsigned('BIGINT', 'BIGINT(20) UNSIGNED'));
        $this->assertTrue($this->isBigIntUnsigned('BigInt', 'BigInt(8) UnSiGnEd'));
    }

    public function test_signed_bigint_is_rejected(): void
    {
        $this->assertFalse($this->isBigIntUnsigned('bigint', 'bigint'));
        $this->assertFalse($this->isBigIntUnsigned('bigint', 'bigint signed'));
    }

    public function test_unsigned_integer_types_other_than_bigint_are_rejected(): void
    {
        $this->assertFalse($this->isBigIntUnsigned('int', 'int(10) unsigned'));
        $this->assertFalse($this->isBigIntUnsigned('int', 'int unsigned'));
    }

    public function test_decimal_and_varchar_are_rejected(): void
    {
        $this->assertFalse($this->isBigIntUnsigned('decimal', 'decimal(20,0) unsigned'));
        $this->assertFalse($this->isBigIntUnsigned('varchar', 'varchar(255)'));
    }

    private function isBigIntUnsigned(string $dataType, string $columnType): bool
    {
        $migration = require dirname(__DIR__, 2)
            .'/database/migrations/2026_07_27_000040_harden_settings_company_id.php';
        $method = new ReflectionMethod($migration, 'isBigIntUnsigned');

        return $method->invoke($migration, [
            'type_name' => $dataType,
            'type' => $columnType,
        ]);
    }
}
