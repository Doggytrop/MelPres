<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

class DropAdvisorIdForeignKeyValidationTest extends TestCase
{
    public function test_no_action_with_set_null_is_accepted(): void
    {
        $this->assertDefinitionIsAccepted($this->foreignKey('NO ACTION', 'SET NULL'));
    }

    public function test_restrict_with_set_null_is_accepted(): void
    {
        $this->assertDefinitionIsAccepted($this->foreignKey('RESTRICT', 'SET NULL'));
    }

    public function test_cascade_on_update_is_rejected(): void
    {
        $this->assertDefinitionIsRejected($this->foreignKey('CASCADE', 'SET NULL'));
    }

    public function test_cascade_on_delete_is_rejected(): void
    {
        $this->assertDefinitionIsRejected($this->foreignKey('RESTRICT', 'CASCADE'));
    }

    public function test_wrong_child_column_is_rejected(): void
    {
        $wrongColumn = $this->foreignKey('RESTRICT', 'SET NULL');
        $wrongColumn['columns'] = ['customer_id'];
        $this->assertDefinitionIsRejected($wrongColumn);
    }

    public function test_wrong_parent_table_is_rejected(): void
    {
        $wrongTable = $this->foreignKey('RESTRICT', 'SET NULL');
        $wrongTable['foreign_table'] = 'companies';
        $this->assertDefinitionIsRejected($wrongTable);
    }

    private function foreignKey(string $onUpdate, string $onDelete): array
    {
        return [
            'name' => 'users_advisor_id_foreign',
            'columns' => ['advisor_id'],
            'foreign_table' => 'users',
            'foreign_columns' => ['id'],
            'on_update' => $onUpdate,
            'on_delete' => $onDelete,
        ];
    }

    private function assertDefinitionIsAccepted(array $foreignKey): void
    {
        $migration = $this->migration();
        $this->validationMethod($migration)->invoke($migration, $foreignKey);
        $this->addToAssertionCount(1);
    }

    private function assertDefinitionIsRejected(array $foreignKey): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'La foreign key users_advisor_id_foreign no tiene la definicion esperada.'
        );

        $migration = $this->migration();
        $this->validationMethod($migration)->invoke($migration, $foreignKey);
    }

    private function validationMethod(object $migration): ReflectionMethod
    {
        return new ReflectionMethod($migration, 'assertExpectedForeignKey');
    }

    private function migration(): object
    {
        return require dirname(__DIR__, 2)
            .'/database/migrations/2026_07_27_000000_drop_advisor_id_from_users_table.php';
    }
}
