<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ReferentialRuleCompatibilityTest extends TestCase
{
    private const MIGRATIONS = [
        '2026_07_27_000080_harden_customer_documents_company_id.php',
        '2026_07_27_000090_harden_restructurings_company_id.php',
    ];

    public function test_restrictive_rule_variants_are_normalized_to_restrict(): void
    {
        foreach (['NO ACTION', 'RESTRICT', 'no action', 'restrict'] as $rule) {
            $this->assertNormalizedRuleAcrossMigrations('RESTRICT', $rule);
        }
    }

    public function test_non_restrictive_rules_remain_distinct(): void
    {
        foreach (['CASCADE', 'SET NULL', 'SET DEFAULT'] as $rule) {
            $this->assertNormalizedRuleAcrossMigrations($rule, $rule);
        }
    }

    public function test_original_loan_restrict_definition_matches_expected_no_action(): void
    {
        $this->assertTrue($this->matchesOriginalLoanForeignKey(
            $this->originalLoanForeignKey('RESTRICT', 'RESTRICT')
        ));
    }

    public function test_original_loan_no_action_definition_is_also_accepted(): void
    {
        $this->assertTrue($this->matchesOriginalLoanForeignKey(
            $this->originalLoanForeignKey('NO ACTION', 'NO ACTION')
        ));
    }

    public function test_original_loan_wrong_structure_or_rules_are_rejected(): void
    {
        $wrongColumn = $this->originalLoanForeignKey('RESTRICT', 'RESTRICT');
        $wrongColumn['columns'] = ['new_loan_id'];
        $this->assertFalse($this->matchesOriginalLoanForeignKey($wrongColumn));

        $wrongTable = $this->originalLoanForeignKey('RESTRICT', 'RESTRICT');
        $wrongTable['foreign_table'] = 'users';
        $this->assertFalse($this->matchesOriginalLoanForeignKey($wrongTable));

        $this->assertFalse($this->matchesOriginalLoanForeignKey(
            $this->originalLoanForeignKey('CASCADE', 'RESTRICT')
        ));
        $this->assertFalse($this->matchesOriginalLoanForeignKey(
            $this->originalLoanForeignKey('RESTRICT', 'SET NULL')
        ));
    }

    private function assertNormalizedRuleAcrossMigrations(string $expected, string $rule): void
    {
        foreach (self::MIGRATIONS as $filename) {
            $migration = $this->migration($filename);
            $method = new ReflectionMethod($migration, 'normalizeReferentialRule');
            $this->assertSame($expected, $method->invoke($migration, $rule), $filename);
        }
    }

    private function matchesOriginalLoanForeignKey(array $foreignKey): bool
    {
        $migration = $this->migration(
            '2026_07_27_000090_harden_restructurings_company_id.php'
        );
        $method = new ReflectionMethod($migration, 'matchesExpectedForeignKey');

        return $method->invoke($migration, $foreignKey, [
            'columns' => ['original_loan_id'],
            'table' => 'loans',
            'foreign_columns' => ['id'],
            'update' => 'NO ACTION',
            'delete' => 'RESTRICT',
        ]);
    }

    private function originalLoanForeignKey(string $onUpdate, string $onDelete): array
    {
        return [
            'name' => 'restructurings_original_loan_id_foreign',
            'columns' => ['original_loan_id'],
            'foreign_table' => 'loans',
            'foreign_columns' => ['id'],
            'on_update' => $onUpdate,
            'on_delete' => $onDelete,
        ];
    }

    private function migration(string $filename): object
    {
        return require dirname(__DIR__, 2).'/database/migrations/'.$filename;
    }
}
