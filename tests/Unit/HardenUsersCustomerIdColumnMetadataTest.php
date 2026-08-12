<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class HardenUsersCustomerIdColumnMetadataTest extends TestCase
{
    public function test_mariadb_nullable_bigint_unsigned_default_null_is_accepted(): void
    {
        $definition = (object) [
            'DATA_TYPE' => 'bigint',
            'COLUMN_TYPE' => 'bigint(20) unsigned',
            'IS_NULLABLE' => 'YES',
            'COLUMN_DEFAULT' => 'NULL',
            'EXTRA' => '',
        ];
        $this->assertTrue($this->definitionMatches($definition, true, ''));
    }

    public function test_mysql_php_null_default_is_accepted(): void
    {
        $this->assertTrue($this->definitionMatches($this->definition(default: null), true, ''));
    }

    public function test_customer_parent_columns_keep_their_expected_metadata(): void
    {
        $this->assertTrue($this->definitionMatches($this->definition(nullable: 'NO'), false, ''));
        $this->assertTrue($this->definitionMatches(
            $this->definition(nullable: 'NO', extra: 'auto_increment'),
            false,
            'auto_increment'
        ));
    }

    public function test_incompatible_type_nullability_default_or_extra_is_rejected(): void
    {
        $this->assertFalse($this->definitionMatches(
            $this->definition(dataType: 'int', columnType: 'int(10) unsigned'),
            true,
            ''
        ));
        $this->assertFalse($this->definitionMatches($this->definition(nullable: 'NO'), true, ''));
        $this->assertFalse($this->definitionMatches($this->definition(default: 0), true, ''));
        $this->assertFalse($this->definitionMatches($this->definition(default: 'active'), true, ''));
        $this->assertFalse($this->definitionMatches($this->definition(default: "'NULL'"), true, ''));
        $this->assertFalse($this->definitionMatches(
            $this->definition(extra: 'auto_increment'),
            true,
            ''
        ));

        $missingDefault = $this->definition();
        unset($missingDefault->COLUMN_DEFAULT);
        $this->assertFalse($this->definitionMatches($missingDefault, true, ''));

        $missingExtra = $this->definition();
        unset($missingExtra->EXTRA);
        $this->assertFalse($this->definitionMatches($missingExtra, true, ''));
    }

    private function definitionMatches(object $definition, bool $nullable, string $extra): bool
    {
        $migration = $this->migration();
        $method = new ReflectionMethod($migration, 'isExpectedColumnDefinition');

        return $method->invoke($migration, $definition, $nullable, $extra);
    }

    private function definition(
        string $dataType = 'bigint',
        string $columnType = 'bigint(20) unsigned',
        string $nullable = 'YES',
        mixed $default = 'NULL',
        string $extra = ''
    ): object {
        return (object) [
            'DATA_TYPE' => $dataType,
            'COLUMN_TYPE' => $columnType,
            'IS_NULLABLE' => $nullable,
            'COLUMN_DEFAULT' => $default,
            'EXTRA' => $extra,
        ];
    }

    private function migration(): object
    {
        return require dirname(__DIR__, 2)
            .'/database/migrations/2026_07_27_000100_harden_users_customer_id.php';
    }
}
