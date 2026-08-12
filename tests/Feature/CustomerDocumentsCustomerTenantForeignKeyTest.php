<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerDocumentsCustomerTenantForeignKeyTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    private const CONSTRAINT = 'cust_docs_company_customer_tenant_fk';

    public function runDatabaseMigrations()
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped(
                'La foreign key compuesta requiere validación definitiva sobre MySQL.'
            );
        }

        $database = strtolower((string) DB::getDatabaseName());

        if (! str_contains($database, 'test')) {
            $this->fail('Las pruebas DDL solo pueden ejecutarse en una base MySQL de pruebas.');
        }

        $this->baseRunDatabaseMigrations();
    }

    public function test_customer_documents_foreign_key_uses_cascade_on_delete(): void
    {
        $foreignKey = $this->foreignKey(self::CONSTRAINT);

        $this->assertNotNull($foreignKey);
        $this->assertSame(self::CONSTRAINT, $foreignKey['name']);
        $this->assertSame(['company_id', 'customer_id'], $foreignKey['columns']);
        $this->assertSame('customers', $foreignKey['foreign_table']);
        $this->assertSame(['company_id', 'id'], $foreignKey['foreign_columns']);
        $this->assertContains(strtolower($foreignKey['on_update']), ['restrict', 'no action']);
        $this->assertSame('cascade', strtolower($foreignKey['on_delete']));
    }

    public function test_valid_document_from_the_same_company_is_accepted(): void
    {
        [$companyId, $customerId] = $this->tenantCustomer('Empresa Uno', 'empresa-uno');

        $this->insertDocument($companyId, $customerId, 'valid.pdf');

        $this->assertDatabaseHas('customer_documents', [
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'original_name' => 'valid.pdf',
        ]);
    }

    public function test_document_from_a_different_company_is_rejected(): void
    {
        [, $customerId] = $this->tenantCustomer('Empresa Uno', 'empresa-uno');
        [$otherCompanyId] = $this->tenantCustomer('Empresa Dos', 'empresa-dos');

        $this->expectException(QueryException::class);

        $this->insertDocument($otherCompanyId, $customerId, 'invalid.pdf');
    }

    public function test_deleting_a_customer_cascades_its_documents(): void
    {
        [$companyId, $customerId] = $this->tenantCustomer('Empresa Uno', 'empresa-uno');
        $this->insertDocument($companyId, $customerId, 'first.pdf');
        $this->insertDocument($companyId, $customerId, 'second.pdf');

        $this->assertSame(
            2,
            DB::table('customer_documents')->where('customer_id', $customerId)->count()
        );

        DB::table('customers')->where('id', $customerId)->delete();

        $this->assertDatabaseMissing('customers', ['id' => $customerId]);
        $this->assertDatabaseMissing('customer_documents', ['customer_id' => $customerId]);
        $this->assertSame(
            0,
            DB::table('customer_documents as document')
                ->leftJoin('customers as customer', 'customer.id', '=', 'document.customer_id')
                ->whereNull('customer.id')
                ->count()
        );
    }

    public function test_rollback_removes_only_composite_foreign_key(): void
    {
        $migration = require database_path(
            'migrations/2026_07_21_000013_add_customer_documents_customer_tenant_foreign_key.php'
        );

        $migration->down();

        $this->assertNull($this->foreignKey(self::CONSTRAINT));
        $this->assertNotNull(
            $this->foreignKey('customer_documents_customer_id_foreign')
        );

        $index = $this->namedIndex('cust_docs_company_customer_type_created_idx');

        $this->assertNotNull($index);
        $this->assertSame(
            ['company_id', 'customer_id'],
            array_slice($index['columns'], 0, 2)
        );
        $this->assertSame(
            ['company_id', 'customer_id', 'type', 'created_at'],
            $index['columns']
        );

        $migration->up();

        $this->assertNotNull($this->foreignKey(self::CONSTRAINT));
        $this->assertNotNull(
            $this->foreignKey('customer_documents_customer_id_foreign')
        );
    }

    private function tenantCustomer(string $companyName, string $slug): array
    {
        $companyId = DB::table('companies')->insertGetId([
            'name' => $companyName,
            'slug' => $slug,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $customerId = DB::table('customers')->insertGetId([
            'company_id' => $companyId,
            'first_name' => 'Cliente',
            'last_name' => $companyName,
            'status' => 'active',
            'score' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$companyId, $customerId];
    }

    private function insertDocument(
        int $companyId,
        int $customerId,
        string $originalName
    ): void {
        DB::table('customer_documents')->insert([
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'type' => 'other',
            'original_name' => $originalName,
            'path' => 'documents/'.$originalName,
            'mime_type' => 'application/pdf',
            'size' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function foreignKey(string $name): ?array
    {
        return collect(Schema::getForeignKeys('customer_documents'))
            ->first(fn (array $foreignKey) => strcasecmp($foreignKey['name'], $name) === 0);
    }

    private function namedIndex(string $name): ?array
    {
        return collect(Schema::getIndexes('customer_documents'))
            ->first(fn (array $index) => strcasecmp($index['name'], $name) === 0);
    }
}
