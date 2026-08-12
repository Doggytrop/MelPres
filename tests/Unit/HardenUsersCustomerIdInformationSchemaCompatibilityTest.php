<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class HardenUsersCustomerIdInformationSchemaCompatibilityTest extends TestCase
{
    private ?object $migrationInstance = null;

    public function test_mysql_visible_index_is_active_case_insensitively(): void
    {
        $this->assertTrue($this->indexIsActive((object) ['IS_VISIBLE' => 'YES'], 'IS_VISIBLE'));
        $this->assertTrue($this->indexIsActive((object) ['IS_VISIBLE' => 'yes'], 'IS_VISIBLE'));
        $this->assertFalse($this->indexIsActive((object) ['IS_VISIBLE' => 'NO'], 'IS_VISIBLE'));
    }

    public function test_mariadb_non_ignored_index_is_active_case_insensitively(): void
    {
        $this->assertTrue($this->indexIsActive((object) ['IGNORED' => 'NO'], 'IGNORED'));
        $this->assertTrue($this->indexIsActive((object) ['IGNORED' => 'no'], 'IGNORED'));
        $this->assertFalse($this->indexIsActive((object) ['IGNORED' => 'YES'], 'IGNORED'));
    }

    public function test_unknown_or_incomplete_index_metadata_fails_closed(): void
    {
        $this->assertFalse($this->indexIsActive((object) ['IS_VISIBLE' => 'UNKNOWN'], 'IS_VISIBLE'));
        $this->assertFalse($this->indexIsActive((object) ['IGNORED' => 'UNKNOWN'], 'IGNORED'));
        $this->assertFalse($this->indexIsActive((object) ['OTHER' => 'YES'], 'IS_VISIBLE'));
        $this->assertFalse($this->indexIsActive(null, 'IS_VISIBLE'));
    }

    public function test_mysql_check_constraint_preserves_enforcement_state(): void
    {
        $active = $this->normalizeCheck($this->check(['ENFORCED' => 'YES']), true);
        $inactive = $this->normalizeCheck($this->check(['ENFORCED' => 'no']), true);

        $this->assertTrue($active['ENFORCEMENT_METADATA_AVAILABLE']);
        $this->assertTrue($active['ENFORCED']);
        $this->assertFalse($inactive['ENFORCED']);
    }

    public function test_mariadb_check_constraint_without_enforced_uses_complete_metadata(): void
    {
        $normalized = $this->normalizeCheck($this->check(), false);

        $this->assertSame('users_email_chk', $normalized['CONSTRAINT_NAME']);
        $this->assertSame('CHECK', $normalized['CONSTRAINT_TYPE']);
        $this->assertSame('(`email` is not null)', $normalized['CHECK_CLAUSE']);
        $this->assertFalse($normalized['ENFORCEMENT_METADATA_AVAILABLE']);
        $this->assertNull($normalized['ENFORCED']);
    }

    public function test_incorrect_or_unknown_constraint_metadata_is_rejected(): void
    {
        $this->assertNull($this->normalizeCheck(null, false));
        $this->assertNull($this->normalizeCheck((object) ['CONSTRAINT_NAME' => 'partial'], false));
        $this->assertNull($this->normalizeCheck($this->check(['CONSTRAINT_TYPE' => 'UNIQUE']), false));
        $this->assertNull($this->normalizeCheck($this->check(['ENFORCED' => 'UNKNOWN']), true));
        $this->assertNull($this->normalizeCheck($this->check(), true));
    }

    private function indexIsActive(?object $metadata, string $column): bool
    {
        return $this->method('isIndexActive')->invoke($this->migration(), $metadata, $column);
    }

    private function normalizeCheck(?object $metadata, bool $hasEnforced): ?array
    {
        return $this->method('normalizeCheckConstraint')->invoke(
            $this->migration(),
            $metadata,
            $hasEnforced
        );
    }

    private function check(array $overrides = []): object
    {
        return (object) array_merge([
            'CONSTRAINT_NAME' => 'users_email_chk',
            'CONSTRAINT_TYPE' => 'CHECK',
            'CHECK_CLAUSE' => '(`email` is not null)',
        ], $overrides);
    }

    private function method(string $name): ReflectionMethod
    {
        return new ReflectionMethod($this->migration(), $name);
    }

    private function migration(): object
    {
        return $this->migrationInstance ??= require dirname(__DIR__, 2)
            .'/database/migrations/2026_07_27_000100_harden_users_customer_id.php';
    }
}
