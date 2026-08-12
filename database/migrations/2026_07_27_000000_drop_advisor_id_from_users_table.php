<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    private const COLUMN = 'advisor_id';

    private const CONSTRAINT = 'users_advisor_id_foreign';

    private const INDEX = 'users_advisor_id_foreign';

    public function up(): void
    {
        if (! Schema::hasColumn('users', self::COLUMN)) {
            return;
        }

        $assignedUsers = DB::table('users')
            ->whereNotNull(self::COLUMN)
            ->count();

        if ($assignedUsers > 0) {
            throw new RuntimeException(
                "No se puede eliminar users.advisor_id: existen {$assignedUsers} filas con valor."
            );
        }

        $foreignKeys = $this->foreignKeysForColumn(self::COLUMN);

        foreach ($foreignKeys as $foreignKey) {
            if (strcasecmp($foreignKey['name'], self::CONSTRAINT) !== 0) {
                throw new RuntimeException(
                    "No se puede eliminar users.advisor_id: existe la foreign key inesperada {$foreignKey['name']}."
                );
            }
        }

        $foreignKey = $this->foreignKey(self::CONSTRAINT);

        if ($foreignKey !== null) {
            $this->assertExpectedForeignKey($foreignKey);

            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(self::CONSTRAINT);
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(self::COLUMN);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', self::COLUMN)) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger(self::COLUMN)
                    ->nullable()
                    ->after('id');
            });
        }

        if (! $this->hasExactIndex('users', [self::COLUMN])) {
            if ($this->hasNamedIndex('users', self::INDEX)) {
                throw new RuntimeException(
                    'No se puede restaurar users.advisor_id: el nombre del indice requerido ya esta en uso.'
                );
            }

            Schema::table('users', function (Blueprint $table) {
                $table->index(self::COLUMN, self::INDEX);
            });
        }

        $foreignKeys = $this->foreignKeysForColumn(self::COLUMN);

        foreach ($foreignKeys as $foreignKey) {
            if (strcasecmp($foreignKey['name'], self::CONSTRAINT) !== 0) {
                throw new RuntimeException(
                    "No se puede restaurar users.advisor_id: existe la foreign key inesperada {$foreignKey['name']}."
                );
            }
        }

        $foreignKey = $this->foreignKey(self::CONSTRAINT);

        if ($foreignKey !== null) {
            $this->assertExpectedForeignKey($foreignKey);

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreign(self::COLUMN, self::CONSTRAINT)
                ->references('id')
                ->on('users')
                ->noActionOnUpdate()
                ->nullOnDelete();
        });
    }

    private function foreignKey(string $name): ?array
    {
        foreach (Schema::getForeignKeys('users') as $foreignKey) {
            if (strcasecmp($foreignKey['name'], $name) === 0) {
                return $foreignKey;
            }
        }

        return null;
    }

    private function foreignKeysForColumn(string $column): array
    {
        return array_values(array_filter(
            Schema::getForeignKeys('users'),
            fn (array $foreignKey) => in_array(
                strtolower($column),
                array_map('strtolower', $foreignKey['columns']),
                true
            )
        ));
    }

    private function assertExpectedForeignKey(array $foreignKey): void
    {
        $isExpected = $this->sameColumns(
            $foreignKey['columns'],
            [self::COLUMN]
        )
            && strcasecmp($foreignKey['foreign_table'], 'users') === 0
            && $this->sameColumns($foreignKey['foreign_columns'], ['id'])
            && $this->normalizeReferentialRule($foreignKey['on_update']) === 'RESTRICT'
            && $this->normalizeReferentialRule($foreignKey['on_delete']) === 'SET NULL';

        if (! $isExpected) {
            throw new RuntimeException(
                'La foreign key users_advisor_id_foreign no tiene la definicion esperada.'
            );
        }
    }

    private function normalizeReferentialRule(string $rule): string
    {
        $normalized = strtoupper($rule);

        return $normalized === 'NO ACTION' ? 'RESTRICT' : $normalized;
    }

    private function hasExactIndex(string $table, array $columns): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($this->sameColumns($index['columns'], $columns)
                && ! (bool) $index['unique']) {
                return true;
            }
        }

        return false;
    }

    private function hasNamedIndex(string $table, string $name): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (strcasecmp($index['name'], $name) === 0) {
                return true;
            }
        }

        return false;
    }

    private function sameColumns(array $actual, array $expected): bool
    {
        return array_map('strtolower', $actual) === array_map('strtolower', $expected);
    }
};
