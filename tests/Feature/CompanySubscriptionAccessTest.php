<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompanySubscriptionAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_active_company_can_access_tenant_routes(): void
    {
        [$company, $admin] = $this->tenant();
        $this->subscription($company, SubscriptionStatus::ACTIVE);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_expired_active_subscription_is_effectively_past_due_and_uses_grace_policy(): void
    {
        Carbon::setTestNow('2026-08-03 12:00:00');
        [$company, $admin] = $this->tenant();
        $subscription = $this->subscription($company, SubscriptionStatus::ACTIVE, now()->addDay());
        $subscription->update(['current_period_end' => now()->subDay()]);

        $this->assertSame(SubscriptionStatus::PAST_DUE, $subscription->fresh()->effectiveStatus());
        $this->actingAs($admin)->get(route('dashboard'))->assertOk();

        $subscription->update(['grace_until' => now()->subSecond()]);
        $this->assertSubscriptionBlocked($admin);
    }

    public function test_expiration_never_changes_suspended_or_cancelled_status(): void
    {
        foreach ([SubscriptionStatus::SUSPENDED, SubscriptionStatus::CANCELLED] as $status) {
            $company = Company::factory()->create();
            $subscription = $this->subscription($company, $status);
            $subscription->update(['current_period_end' => now()->subYear()]);

            $this->assertSame($status, $subscription->fresh()->effectiveStatus());
            $this->assertSame($status, $subscription->status);
        }
    }

    public function test_past_due_company_with_current_grace_can_access(): void
    {
        Carbon::setTestNow('2026-07-31 12:00:00');
        [$company, $admin] = $this->tenant();
        $this->subscription($company, SubscriptionStatus::PAST_DUE, now());

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_past_due_company_with_expired_grace_is_blocked(): void
    {
        Carbon::setTestNow('2026-07-31 12:00:00');
        [$company, $admin] = $this->tenant();
        $this->subscription($company, SubscriptionStatus::PAST_DUE, now()->subSecond());

        $this->assertSubscriptionBlocked($admin);
    }

    public function test_past_due_company_without_grace_is_blocked(): void
    {
        [$company, $admin] = $this->tenant();
        $this->subscription($company, SubscriptionStatus::PAST_DUE);

        $this->assertSubscriptionBlocked($admin);
    }

    public function test_suspended_company_is_blocked(): void
    {
        [$company, $admin] = $this->tenant();
        $this->subscription($company, SubscriptionStatus::SUSPENDED);

        $this->assertSubscriptionBlocked($admin);
    }

    public function test_suspended_company_shows_the_same_safe_screen_to_every_tenant_role(): void
    {
        $company = Company::factory()->create();
        $this->subscription($company, SubscriptionStatus::SUSPENDED);

        foreach (['admin', 'advisor', 'collector', 'customer'] as $role) {
            $user = $this->tenantUser($company, $role);

            $this->actingAs($user)
                ->get($this->tenantRoute($role))
                ->assertForbidden()
                ->assertSee('Cuenta suspendida')
                ->assertSee('Tus datos permanecen seguros')
                ->assertSee('Cerrar sesión')
                ->assertSee('action="'.route('logout').'"', false);
        }
    }

    public function test_suspended_user_cannot_bypass_the_block_through_direct_business_resources(): void
    {
        [$company, $admin] = $this->tenant();
        $customer = Customer::factory()->for($company)->create();
        $this->subscription($company, SubscriptionStatus::SUSPENDED);

        $this->actingAs($admin)
            ->get(route('customers.show', $customer))
            ->assertForbidden()
            ->assertSee('Cuenta suspendida');
    }

    public function test_suspension_of_one_company_does_not_block_an_active_company(): void
    {
        [$suspendedCompany, $suspendedAdmin] = $this->tenant();
        $this->subscription($suspendedCompany, SubscriptionStatus::SUSPENDED);
        [$activeCompany, $activeAdmin] = $this->tenant();
        $this->subscription($activeCompany, SubscriptionStatus::ACTIVE);

        $this->assertSubscriptionBlocked($suspendedAdmin);
        $this->actingAs($activeAdmin)->get(route('dashboard'))->assertOk();
    }

    public function test_logout_remains_available_from_the_suspended_screen(): void
    {
        [$company, $admin] = $this->tenant();
        $this->subscription($company, SubscriptionStatus::SUSPENDED);

        $this->assertSubscriptionBlocked($admin);

        $this->followingRedirects()
            ->post(route('logout'))
            ->assertOk()
            ->assertSee('Iniciar sesi');

        $this->assertGuest();
    }

    public function test_cancelled_company_is_blocked(): void
    {
        [$company, $admin] = $this->tenant();
        $this->subscription($company, SubscriptionStatus::CANCELLED);

        $this->assertSubscriptionBlocked($admin);
    }

    public function test_company_without_subscription_is_blocked(): void
    {
        [, $admin] = $this->tenant();

        $this->assertSubscriptionBlocked($admin);
    }

    public function test_global_superadmin_is_not_blocked_by_suspended_companies(): void
    {
        $company = Company::factory()->create();
        $this->subscription($company, SubscriptionStatus::SUSPENDED);
        $superadmin = User::factory()->create([
            'company_id' => null,
            'role' => 'superadmin',
        ]);

        $this->actingAs($superadmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk();
    }

    public function test_suspended_admin_cannot_bypass_block_with_a_direct_url(): void
    {
        [$company, $admin] = $this->tenant();
        $customer = Customer::factory()->for($company)->create();
        $this->subscription($company, SubscriptionStatus::SUSPENDED);

        $this->actingAs($admin)
            ->get(route('customers.show', $customer))
            ->assertForbidden();
    }

    public function test_reactivating_subscription_restores_access_immediately(): void
    {
        [$company, $admin] = $this->tenant();
        $subscription = $this->subscription($company, SubscriptionStatus::SUSPENDED);

        $this->assertSubscriptionBlocked($admin);

        $subscription->update([
            'status' => SubscriptionStatus::ACTIVE,
            'suspended_at' => null,
        ]);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_reactivation_restores_access_for_every_tenant_role(): void
    {
        $company = Company::factory()->create();
        $subscription = $this->subscription($company, SubscriptionStatus::SUSPENDED);
        $users = collect(['admin', 'advisor', 'collector', 'customer'])
            ->mapWithKeys(fn (string $role) => [$role => $this->tenantUser($company, $role)]);

        foreach ($users as $role => $user) {
            $this->actingAs($user)->get($this->tenantRoute($role))->assertForbidden();
        }

        $subscription->update([
            'status' => SubscriptionStatus::ACTIVE,
            'suspended_at' => null,
        ]);

        foreach ($users as $role => $user) {
            $this->actingAs($user)->get($this->tenantRoute($role))->assertOk();
        }
    }

    public function test_suspension_does_not_modify_tenant_business_data(): void
    {
        [$company, $admin] = $this->tenant();
        $subscription = $this->subscription($company, SubscriptionStatus::ACTIVE);
        $customer = Customer::factory()->for($company)->create();
        $loan = Loan::factory()->for($customer)->create();
        $payment = Payment::factory()->for($loan)->create([
            'company_id' => $company->id,
            'recorded_by' => $admin->id,
        ]);
        $document = CustomerDocument::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'type' => 'other',
            'original_name' => 'documento.pdf',
            'path' => 'customer-documents/documento.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
        ]);

        $subscription->update([
            'status' => SubscriptionStatus::SUSPENDED,
            'suspended_at' => now(),
        ]);

        $this->assertSubscriptionBlocked($admin);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'company_id' => $company->id]);
        $this->assertDatabaseHas('loans', ['id' => $loan->id, 'company_id' => $company->id]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'company_id' => $company->id]);
        $this->assertDatabaseHas('customer_documents', ['id' => $document->id, 'company_id' => $company->id]);
    }

    public function test_backfill_creates_one_active_subscription_for_each_existing_company(): void
    {
        $first = Company::factory()->create();
        $second = Company::factory()->create();
        $migration = require database_path('migrations/2026_07_31_000001_backfill_company_subscriptions.php');

        $migration->up();
        $migration->up();

        foreach ([$first, $second] as $company) {
            $this->assertDatabaseHas('company_subscriptions', [
                'company_id' => $company->id,
                'status' => SubscriptionStatus::ACTIVE->value,
            ]);
            $this->assertSame(1, CompanySubscription::where('company_id', $company->id)->count());
        }
    }

    public function test_unique_constraint_prevents_two_subscriptions_for_one_company(): void
    {
        $company = Company::factory()->create();
        $this->subscription($company, SubscriptionStatus::ACTIVE);

        $this->expectException(QueryException::class);
        $this->subscription($company, SubscriptionStatus::SUSPENDED);
    }

    public function test_foreign_key_rejects_a_nonexistent_company(): void
    {
        $this->expectException(QueryException::class);

        DB::table('company_subscriptions')->insert([
            'company_id' => 999999,
            'status' => SubscriptionStatus::ACTIVE->value,
            'started_at' => now(),
            'current_period_start' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function tenant(): array
    {
        $company = Company::factory()->create();
        $admin = User::factory()->for($company)->create(['role' => 'admin']);

        return [$company, $admin];
    }

    private function subscription(
        Company $company,
        SubscriptionStatus $status,
        mixed $graceUntil = null
    ): CompanySubscription {
        return CompanySubscription::create([
            'company_id' => $company->id,
            'status' => $status,
            'started_at' => now(),
            'current_period_start' => now(),
            'current_period_end' => null,
            'grace_until' => $graceUntil,
            'suspended_at' => $status === SubscriptionStatus::SUSPENDED ? now() : null,
            'cancelled_at' => $status === SubscriptionStatus::CANCELLED ? now() : null,
        ]);
    }

    private function assertSubscriptionBlocked(User $user): void
    {
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden()
            ->assertSee('Cuenta suspendida')
            ->assertSee('Tus datos permanecen seguros')
            ->assertSee('Cerrar sesión');
    }

    private function tenantUser(Company $company, string $role): User
    {
        $customerId = $role === 'customer'
            ? Customer::factory()->for($company)->create()->id
            : null;

        return User::factory()->for($company)->create([
            'role' => $role,
            'customer_id' => $customerId,
        ]);
    }

    private function tenantRoute(string $role): string
    {
        return match ($role) {
            'collector' => route('collector.index'),
            'customer' => route('portal.index'),
            default => route('dashboard'),
        };
    }
}
