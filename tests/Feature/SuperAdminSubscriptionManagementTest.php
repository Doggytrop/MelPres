<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\SaasActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SuperAdminSubscriptionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[DataProvider('renewalYearsProvider')]
    public function test_renewal_extends_a_future_period_by_the_paid_years(int $years): void
    {
        Carbon::setTestNow('2026-08-03 10:00:00');
        [$company, $subscription] = $this->tenantSubscription(
            SubscriptionStatus::PAST_DUE,
            Carbon::parse('2027-08-10 10:00:00')
        );
        $expectedEnd = $subscription->current_period_end->copy()->addYearsNoOverflow($years);
        $subscription->update([
            'grace_until' => $subscription->current_period_end->copy()->addDays(5),
        ]);

        $this->actingAs($this->superadmin())
            ->post(route('superadmin.companies.renew', $company), [
                'subscription_years' => $years,
                'grace_days' => 10,
                'status' => 'cancelled',
                'current_period_end' => '2040-01-01 00:00:00',
                'company_id' => 999,
            ])
            ->assertRedirect(route('superadmin.companies.show', $company));

        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertTrue($subscription->current_period_start->equalTo('2027-08-10 10:00:00'));
        $this->assertTrue($subscription->current_period_end->equalTo($expectedEnd));
        $this->assertNull($subscription->grace_until);
        $this->assertNull($subscription->suspended_at);
        $this->assertNull($subscription->cancelled_at);

        $log = SaasActivityLog::where('action', 'company_renewed')->firstOrFail();
        $this->assertSame($years, $log->new_values['renewal']['years_added']);
        $this->assertSame(
            $expectedEnd->toISOString(),
            $log->new_values['subscription']['current_period_end']
        );
    }

    public function test_renewal_of_an_expired_suspended_company_starts_from_now_and_preserves_tenant_data(): void
    {
        Carbon::setTestNow('2026-08-03 10:00:00');
        [$company, $subscription, $admin] = $this->tenantSubscription(
            SubscriptionStatus::SUSPENDED,
            Carbon::parse('2025-08-10 10:00:00')
        );
        $customer = Customer::factory()->for($company)->create();
        $loan = Loan::factory()->for($customer)->create();
        $payment = Payment::factory()->for($loan)->create([
            'company_id' => $company->id,
            'recorded_by' => $admin->id,
        ]);

        $this->actingAs($this->superadmin())
            ->post(route('superadmin.companies.renew', $company), [
                'subscription_years' => 1,
            ])
            ->assertRedirect(route('superadmin.companies.show', $company));

        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertTrue($subscription->current_period_start->equalTo(now()));
        $this->assertTrue($subscription->current_period_end->equalTo(now()->addYearNoOverflow()));
        $this->assertNull($subscription->grace_until);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'company_id' => $company->id]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'company_id' => $company->id]);
        $this->assertDatabaseHas('loans', ['id' => $loan->id, 'company_id' => $company->id]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'company_id' => $company->id]);
    }

    public function test_grace_is_updated_explicitly_and_audited(): void
    {
        [$company, $subscription] = $this->tenantSubscription(
            SubscriptionStatus::ACTIVE,
            now()->addYear()
        );
        $graceUntil = $subscription->current_period_end->copy()->addDays(5);

        $this->actingAs($this->superadmin())
            ->post(route('superadmin.companies.grace.update', $company), [
                'grace_until' => $graceUntil->toDateTimeString(),
                'status' => 'cancelled',
                'company_id' => 999,
            ])
            ->assertRedirect(route('superadmin.companies.show', $company));

        $this->assertTrue($subscription->fresh()->grace_until->equalTo($graceUntil));
        $log = SaasActivityLog::where('action', 'company_grace_updated')->firstOrFail();
        $this->assertNull($log->old_values['subscription']['grace_until']);
        $this->assertSame($graceUntil->toISOString(), $log->new_values['subscription']['grace_until']);
    }

    public function test_grace_before_the_current_period_end_is_rejected(): void
    {
        [$company, $subscription] = $this->tenantSubscription(
            SubscriptionStatus::ACTIVE,
            now()->addYear()
        );

        $this->actingAs($this->superadmin())
            ->from(route('superadmin.companies.show', $company))
            ->post(route('superadmin.companies.grace.update', $company), [
                'grace_until' => $subscription->current_period_end->copy()->subSecond()->toDateTimeString(),
            ])
            ->assertRedirect(route('superadmin.companies.show', $company))
            ->assertSessionHasErrors('grace_until');

        $this->assertNull($subscription->fresh()->grace_until);
    }

    public function test_existing_grace_can_be_removed_explicitly_without_reactivating_status(): void
    {
        [$company, $subscription] = $this->tenantSubscription(
            SubscriptionStatus::PAST_DUE,
            now()->subDay()
        );
        $subscription->update(['grace_until' => now()->addDays(3)]);

        $this->actingAs($this->superadmin())
            ->delete(route('superadmin.companies.grace.remove', $company))
            ->assertRedirect(route('superadmin.companies.show', $company));

        $subscription->refresh();
        $this->assertNull($subscription->grace_until);
        $this->assertSame(SubscriptionStatus::PAST_DUE, $subscription->status);
        $this->assertDatabaseHas('saas_activity_logs', [
            'action' => 'company_grace_removed',
            'subject_id' => $company->id,
        ]);
    }

    public function test_invalid_transitions_are_rejected_by_the_backend(): void
    {
        [$company, $subscription] = $this->tenantSubscription(
            SubscriptionStatus::SUSPENDED,
            now()->addYear()
        );

        $this->actingAs($this->superadmin())
            ->post(route('superadmin.companies.suspend', $company))
            ->assertSessionHasErrors('subscription');

        $this->actingAs($this->superadmin())
            ->post(route('superadmin.companies.grace.update', $company), [
                'grace_until' => now()->addYear()->addDay()->toDateTimeString(),
            ])
            ->assertSessionHasErrors('subscription');

        $this->actingAs($this->superadmin())
            ->delete(route('superadmin.companies.grace.remove', $company))
            ->assertSessionHasErrors('subscription');

        $this->assertSame(SubscriptionStatus::SUSPENDED, $subscription->fresh()->status);
    }

    public function test_cancelled_subscription_cannot_be_renewed_without_reactivation(): void
    {
        [$company, $subscription] = $this->tenantSubscription(
            SubscriptionStatus::CANCELLED,
            now()->subYear()
        );

        $this->actingAs($this->superadmin())
            ->post(route('superadmin.companies.renew', $company), [
                'subscription_years' => 1,
            ])
            ->assertSessionHasErrors('subscription');

        $this->assertSame(SubscriptionStatus::CANCELLED, $subscription->fresh()->status);
    }

    public function test_action_visibility_matches_each_commercial_status(): void
    {
        $superadmin = $this->superadmin();
        $cases = [
            [SubscriptionStatus::ACTIVE, ['Suspender empresa', 'Cancelar suscripción']],
            [SubscriptionStatus::PAST_DUE, ['Suspender empresa', 'Cancelar suscripción']],
            [SubscriptionStatus::SUSPENDED, ['Renovar suscripción', 'Reactivar empresa', 'Cancelar suscripción']],
            [SubscriptionStatus::CANCELLED, ['Reactivar empresa']],
        ];

        foreach ($cases as [$status, $expectedActions]) {
            [$company] = $this->tenantSubscription($status, now()->addYear());
            $response = $this->actingAs($superadmin)
                ->get(route('superadmin.companies.show', $company))
                ->assertOk();

            foreach ($expectedActions as $action) {
                $response->assertSee($action);
            }
        }
    }

    public function test_cancelled_subscription_only_exposes_reactivation(): void
    {
        [$company] = $this->tenantSubscription(
            SubscriptionStatus::CANCELLED,
            now()->subYear()
        );

        $this->actingAs($this->superadmin())
            ->get(route('superadmin.companies.show', $company))
            ->assertOk()
            ->assertSee('Reactivar empresa')
            ->assertDontSee('Renovar suscripción')
            ->assertDontSee('Suspender empresa')
            ->assertDontSee('Cancelar suscripción');
    }

    public function test_invalid_renewal_years_are_rejected_and_tenant_admin_cannot_manage_subscription(): void
    {
        [$company, $subscription, $admin] = $this->tenantSubscription(
            SubscriptionStatus::ACTIVE,
            now()->addYear()
        );
        $originalEnd = $subscription->current_period_end;

        $this->actingAs($this->superadmin())
            ->post(route('superadmin.companies.renew', $company), [
                'subscription_years' => 4,
            ])
            ->assertSessionHasErrors('subscription_years');

        $this->actingAs($admin)
            ->post(route('superadmin.companies.renew', $company), [
                'subscription_years' => 1,
            ])
            ->assertForbidden();

        $this->assertTrue($subscription->fresh()->current_period_end->equalTo($originalEnd));
    }

    public static function renewalYearsProvider(): array
    {
        return [
            'one year' => [1],
            'two years' => [2],
            'three years' => [3],
            'five years' => [5],
        ];
    }

    private function tenantSubscription(
        SubscriptionStatus $status,
        Carbon $periodEnd
    ): array {
        $company = Company::factory()->create();
        $subscription = CompanySubscription::create([
            'company_id' => $company->id,
            'status' => $status,
            'started_at' => now()->subYear(),
            'current_period_start' => now()->subYear(),
            'current_period_end' => $periodEnd,
            'grace_until' => null,
            'suspended_at' => $status === SubscriptionStatus::SUSPENDED ? now() : null,
            'cancelled_at' => $status === SubscriptionStatus::CANCELLED ? now() : null,
        ]);
        $admin = User::factory()->for($company)->create(['role' => 'admin']);

        return [$company, $subscription, $admin];
    }

    private function superadmin(): User
    {
        return User::factory()->create([
            'company_id' => null,
            'role' => 'superadmin',
        ]);
    }
}
