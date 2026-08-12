<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\User;
use App\Services\CompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerDocumentPrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_user_from_same_company_can_view_document_inline(): void
    {
        [$company, $user, $customer] = $this->tenant('Empresa Uno', 'empresa-uno');
        $document = $this->document($company, $customer, 'private-document.pdf');
        Storage::disk('local')->put($document->path, 'private-content');

        $response = $this->actingAs($user)->get(route('customer-documents.view', $document));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeaderContains('content-disposition', 'inline')
            ->assertHeaderContains('content-disposition', 'private-document.pdf');
        $this->assertSame('private-content', $response->streamedContent());

        $this->actingAs($user)
            ->get(route('customer-documents.download', $document))
            ->assertOk()
            ->assertDownload('private-document.pdf');
    }

    public function test_user_from_another_company_receives_404(): void
    {
        [$company, , $customer] = $this->tenant('Empresa Uno', 'empresa-uno');
        [, $otherUser] = $this->tenant('Empresa Dos', 'empresa-dos');
        $document = $this->document($company, $customer);
        Storage::disk('local')->put($document->path, 'private-content');

        $this->actingAs($otherUser)
            ->get(route('customer-documents.view', $document))
            ->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_access_document(): void
    {
        [$company, , $customer] = $this->tenant('Empresa Uno', 'empresa-uno');
        $document = $this->document($company, $customer);

        $this->get(route('customer-documents.view', $document))
            ->assertRedirect(route('login'));
    }

    public function test_missing_physical_document_returns_404(): void
    {
        [$company, $user, $customer] = $this->tenant('Empresa Uno', 'empresa-uno');
        $document = $this->document($company, $customer);

        $this->actingAs($user)
            ->get(route('customer-documents.view', $document))
            ->assertNotFound();
    }

    public function test_unknown_document_id_returns_404(): void
    {
        [, $user] = $this->tenant('Empresa Uno', 'empresa-uno');

        $this->actingAs($user)
            ->get('/customer-documents/999999/view')
            ->assertNotFound();
    }

    public function test_customer_view_uses_protected_urls_instead_of_public_storage_urls(): void
    {
        [$company, $user, $customer] = $this->tenant('Empresa Uno', 'empresa-uno');
        $document = $this->document($company, $customer, 'profile.jpg', 'profile_photo', 'image/jpeg');

        $response = $this->actingAs($user)->get(route('customers.show', $customer));

        $response
            ->assertOk()
            ->assertSee(route('customer-documents.view', $document), false)
            ->assertSee(route('customer-documents.download', $document), false)
            ->assertDontSee(asset('storage/'.$document->path), false);
    }

    public function test_profile_photo_relation_returns_a_photo_from_the_same_company(): void
    {
        [$company, , $customer] = $this->tenant('Empresa Uno', 'empresa-uno');
        $document = $this->document($company, $customer, 'profile.jpg', 'profile_photo', 'image/jpeg');

        $customer->load('profilePhoto');

        $this->assertTrue($customer->relationLoaded('profilePhoto'));
        $this->assertTrue($document->is($customer->profilePhoto));
    }

    public function test_profile_photo_relation_ignores_another_customers_photo(): void
    {
        [, , $customer] = $this->tenant('Empresa Uno', 'empresa-uno');
        [$otherCompany, , $otherCustomer] = $this->tenant('Empresa Dos', 'empresa-dos');

        $this->document(
            $otherCompany,
            $otherCustomer,
            'foreign-profile.jpg',
            'profile_photo',
            'image/jpeg'
        );

        $customer->load('profilePhoto');

        $this->assertNull($customer->profilePhoto);
    }

    public function test_photo_url_uses_the_current_fallback_for_another_customers_photo(): void
    {
        [$company, , $customer] = $this->tenant('Empresa Uno', 'empresa-uno');
        [$otherCompany, , $otherCustomer] = $this->tenant('Empresa Dos', 'empresa-dos');
        $this->document(
            $otherCompany,
            $otherCustomer,
            'foreign-profile.jpg',
            'profile_photo',
            'image/jpeg'
        );
        app(CompanyContext::class)->setCompany($company);

        try {
            $this->assertNull($customer->fresh()->photo_url);
        } finally {
            app(CompanyContext::class)->clear();
        }
    }

    public function test_photo_url_for_a_valid_photo_uses_only_the_protected_route(): void
    {
        [$company, , $customer] = $this->tenant('Empresa Uno', 'empresa-uno');
        $document = $this->document($company, $customer, 'profile.jpg', 'profile_photo', 'image/jpeg');
        app(CompanyContext::class)->setCompany($company);

        try {
            $photoUrl = $customer->fresh()->photo_url;

            $this->assertSame(route('customer-documents.view', $document), $photoUrl);
            $this->assertStringNotContainsString('/storage/', $photoUrl);
        } finally {
            app(CompanyContext::class)->clear();
        }
    }

    public function test_customer_list_and_profile_do_not_lazy_load_profile_photo(): void
    {
        [$company, $user, $customer] = $this->tenant('Empresa Uno', 'empresa-uno');
        $this->document($company, $customer, 'profile.jpg', 'profile_photo', 'image/jpeg');
        Model::preventLazyLoading();

        try {
            $this->actingAs($user)
                ->get(route('customers.index'))
                ->assertOk();

            $this->actingAs($user)
                ->get(route('customers.show', $customer))
                ->assertOk();
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_user_from_another_company_cannot_obtain_photo_metadata(): void
    {
        [$company, , $customer] = $this->tenant('Empresa Uno', 'empresa-uno');
        [, $otherUser] = $this->tenant('Empresa Dos', 'empresa-dos');
        $document = $this->document(
            $company,
            $customer,
            'profile.jpg',
            'profile_photo',
            'image/jpeg'
        );

        $this->actingAs($otherUser)
            ->get(route('customer-documents.view', $document))
            ->assertNotFound();

        $this->actingAs($otherUser)
            ->get(route('customers.show', $customer))
            ->assertNotFound();
    }

    public function test_new_documents_are_stored_only_on_private_disk(): void
    {
        [$company, $user, $customer] = $this->tenant('Empresa Uno', 'empresa-uno');
        $file = UploadedFile::fake()->image('identity.jpg');

        $this->actingAs($user)
            ->post(route('customers.documents.store', $customer), [
                'type' => 'id_front',
                'file' => $file,
                'notes' => 'Documento privado',
            ])
            ->assertRedirect(route('customers.show', $customer));

        $document = CustomerDocument::where('customer_id', $customer->id)->firstOrFail();

        $this->assertSame($company->id, $document->company_id);
        $this->assertStringStartsWith(
            "companies/{$company->id}/customers/{$customer->id}/documents/",
            $document->path
        );
        Storage::disk('local')->assertExists($document->path);
        Storage::disk('public')->assertMissing($document->path);
    }

    public function test_authorized_legacy_public_document_is_served_through_protected_endpoint(): void
    {
        [$company, $user, $customer] = $this->tenant('Empresa Uno', 'empresa-uno');
        $document = $this->document($company, $customer, 'legacy.pdf');
        Storage::disk('public')->put($document->path, 'legacy-content');

        $response = $this->actingAs($user)->get(route('customer-documents.view', $document));

        $response->assertOk();
        $this->assertSame('legacy-content', $response->streamedContent());
    }

    public function test_deleting_document_removes_its_private_file(): void
    {
        [$company, $user, $customer] = $this->tenant('Empresa Uno', 'empresa-uno');
        $document = $this->document($company, $customer);
        Storage::disk('local')->put($document->path, 'private-content');

        $this->actingAs($user)
            ->delete(route('customers.documents.destroy', [$customer, $document]))
            ->assertRedirect(route('customers.show', $customer));

        Storage::disk('local')->assertMissing($document->path);
        $this->assertDatabaseMissing('customer_documents', ['id' => $document->id]);
    }

    public function test_deleting_legacy_document_removes_its_public_file(): void
    {
        [$company, $user, $customer] = $this->tenant('Empresa Uno', 'empresa-uno');
        $document = $this->document($company, $customer, 'legacy-delete.pdf');
        Storage::disk('public')->put($document->path, 'legacy-content');

        $this->actingAs($user)
            ->delete(route('customers.documents.destroy', [$customer, $document]))
            ->assertRedirect(route('customers.show', $customer));

        Storage::disk('public')->assertMissing($document->path);
        $this->assertDatabaseMissing('customer_documents', ['id' => $document->id]);
    }

    public function test_unsafe_stored_path_is_never_resolved(): void
    {
        [$company, $user, $customer] = $this->tenant('Empresa Uno', 'empresa-uno');
        $document = $this->document($company, $customer);
        $document->update(['path' => '../private-document.pdf']);

        $this->actingAs($user)
            ->get(route('customer-documents.view', $document))
            ->assertNotFound();
    }

    private function tenant(string $name, string $slug): array
    {
        $company = Company::create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
        ]);

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'started_at' => now(),
            'current_period_start' => now(),
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
        ]);

        $customer = Customer::create([
            'company_id' => $company->id,
            'first_name' => 'Cliente',
            'last_name' => $name,
            'phone' => fake()->unique()->numerify('##########'),
            'status' => 'active',
        ]);

        return [$company, $user, $customer];
    }

    private function document(
        Company $company,
        Customer $customer,
        string $name = 'document.pdf',
        string $type = 'id_front',
        string $mimeType = 'application/pdf'
    ): CustomerDocument {
        return CustomerDocument::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'type' => $type,
            'original_name' => $name,
            'path' => "companies/{$company->id}/customers/{$customer->id}/documents/{$name}",
            'mime_type' => $mimeType,
            'size' => 15,
        ]);
    }
}
