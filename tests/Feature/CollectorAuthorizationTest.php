<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CollectorAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_collector_is_authorized_to_access_operational_panel(): void
    {
        [$company, $collector] = $this->tenant('Empresa Uno', 'empresa-uno', 'collector');
        $this->loan($company);

        $this->actingAs($collector)->get(route('collector.index'))->assertOk();
    }

    public function test_advisor_receives_403(): void
    {
        [, $advisor] = $this->tenant('Empresa Uno', 'empresa-uno', 'advisor');

        $this->actingAs($advisor)->get(route('collector.index'))->assertForbidden();
    }

    public function test_customer_receives_403(): void
    {
        [, $customerUser] = $this->tenant('Empresa Uno', 'empresa-uno', 'customer');

        $this->actingAs($customerUser)->get(route('collector.index'))->assertForbidden();
    }

    public function test_admin_and_superadmin_do_not_receive_operational_access(): void
    {
        [, $admin] = $this->tenant('Empresa Uno', 'empresa-uno', 'admin');
        [, $superadmin] = $this->tenant('Empresa Dos', 'empresa-dos', 'superadmin');

        $this->actingAs($admin)->get(route('collector.index'))->assertForbidden();
        $this->actingAs($superadmin)->get(route('collector.index'))->assertForbidden();
    }

    public function test_loan_from_another_company_returns_404_and_creates_no_payment(): void
    {
        [, $collector] = $this->tenant('Empresa Uno', 'empresa-uno', 'collector');
        [$otherCompany] = $this->tenant('Empresa Dos', 'empresa-dos', 'admin');
        $otherLoan = $this->loan($otherCompany);

        $this->actingAs($collector)
            ->post(route('collector.collect', $otherLoan), ['amount_paid' => 10])
            ->assertNotFound();

        $this->assertDatabaseMissing('payments', ['loan_id' => $otherLoan->id]);
    }

    public function test_request_company_and_related_ids_are_ignored_when_collecting(): void
    {
        [$company, $collector] = $this->tenant('Empresa Uno', 'empresa-uno', 'collector');
        [$otherCompany] = $this->tenant('Empresa Dos', 'empresa-dos', 'admin');
        $loan = $this->loan($company);
        $otherLoan = $this->loan($otherCompany);

        $this->actingAs($collector)
            ->post(route('collector.collect', $loan), [
                'amount_paid' => 10,
                'company_id' => $otherCompany->id,
                'loan_id' => $otherLoan->id,
                'customer_id' => $otherLoan->customer_id,
            ])
            ->assertRedirect();

        $payment = Payment::where('loan_id', $loan->id)->firstOrFail();

        $this->assertSame($company->id, $payment->company_id);
        $this->assertSame($loan->id, $payment->loan_id);
        $this->assertSame($collector->id, $payment->recorded_by);
        $this->assertDatabaseMissing('payments', ['loan_id' => $otherLoan->id]);
    }

    public function test_payment_activity_log_and_collector_cash_summary_stay_in_active_company(): void
    {
        [$company, $collector] = $this->tenant('Empresa Uno', 'empresa-uno', 'collector');
        $loan = $this->loan($company);

        $this->actingAs($collector)
            ->post(route('collector.collect', $loan), ['amount_paid' => 10, 'company_id' => 999])
            ->assertRedirect();

        $payment = Payment::where('loan_id', $loan->id)->firstOrFail();

        $this->assertSame($company->id, $payment->company_id);
        $this->assertSame($collector->id, $payment->recorded_by);
        $logs = ActivityLog::where('company_id', $company->id)
            ->where('action', 'payment')
            ->where('module', 'payments')
            ->where('model_type', Loan::class)
            ->where('model_id', $loan->id);

        $this->assertSame(1, $logs->count());

        $log = $logs->firstOrFail();
        $this->assertSame($collector->id, $log->user_id);
        $this->assertSame(
            'Registró pago por $10.00 en préstamo #' . $loan->id,
            $log->description
        );

        $this->actingAs($collector)
            ->get(route('collector.index'))
            ->assertOk()
            ->assertSee('10.00');
    }

    public function test_collector_ticket_actions_render_only_own_tenant_payment_data(): void
    {
        [$company, $collector] = $this->tenant('Financiera del Desierto', 'financiera-desierto', 'collector');
        $loan = $this->loan($company);
        $loan->update(['remaining_balance' => 75]);
        $customer = $loan->customer;
        $customer->update(['phone' => '662 123 4567']);

        $payment = Payment::create([
            'company_id' => $company->id,
            'loan_id' => $loan->id,
            'amount_paid' => 25,
            'payment_date' => now()->toDateString(),
            'payment_type' => 'capital',
            'recorded_by' => $collector->id,
        ]);

        [$otherCompany, $otherCollector] = $this->tenant('Empresa Ajena', 'empresa-ajena', 'collector');
        $otherLoan = $this->loan($otherCompany);
        $otherPayment = Payment::create([
            'company_id' => $otherCompany->id,
            'loan_id' => $otherLoan->id,
            'amount_paid' => 99,
            'payment_date' => now()->toDateString(),
            'payment_type' => 'capital',
            'recorded_by' => $otherCollector->id,
        ]);

        $response = $this->actingAs($collector)->get(route('collector.index'));

        $response->assertOk()
            ->assertSee('title="Imprimir ticket"', false)
            ->assertSee('aria-label="Imprimir ticket"', false)
            ->assertSee('title="Enviar por WhatsApp"', false)
            ->assertSee('aria-label="Enviar por WhatsApp"', false)
            ->assertSee('id="printableTicket"', false)
            ->assertSee('data-ticket-empresa="Financiera del Desierto"', false)
            ->assertSee('data-ticket-customer="'.$customer->full_name.'"', false)
            ->assertSee('data-ticket-phone="662 123 4567"', false)
            ->assertSee('data-ticket-loan="'.$loan->id.'"', false)
            ->assertSee('data-ticket-amount="25.00"', false)
            ->assertSee('data-ticket-balance="75.00"', false)
            ->assertSee('data-ticket-payment-id="'.$payment->id.'"', false)
            ->assertDontSee('data-ticket-empresa="Empresa Ajena"', false)
            ->assertDontSee('data-ticket-payment-id="'.$otherPayment->id.'"', false)
            ->assertDontSee('data-ticket-amount="99.00"', false);
    }

    public function test_failed_payment_transaction_creates_no_activity_log(): void
    {
        [$company, $collector] = $this->tenant('Empresa Uno', 'empresa-uno', 'collector');
        $loan = $this->loan($company);

        Event::listen('eloquent.creating: '.Payment::class, function () {
            throw new \RuntimeException('Forced payment failure');
        });

        $this->withExceptionHandling()
            ->actingAs($collector)
            ->post(route('collector.collect', $loan), ['amount_paid' => 10])
            ->assertServerError();

        $this->assertDatabaseMissing('payments', ['loan_id' => $loan->id]);
        $this->assertSame(
            0,
            ActivityLog::where('company_id', $company->id)
                ->where('action', 'payment')
                ->where('module', 'payments')
                ->where('model_type', Loan::class)
                ->where('model_id', $loan->id)
                ->count()
        );
    }

    public function test_admin_can_configure_only_collectors_from_its_company(): void
    {
        [$company, $admin] = $this->tenant('Empresa Uno', 'empresa-uno', 'admin');
        $collector = $this->user($company, 'collector');
        [$otherCompany] = $this->tenant('Empresa Dos', 'empresa-dos', 'admin');
        $otherCollector = $this->user($otherCompany, 'collector');

        $this->actingAs($admin)
            ->post(route('settings.collectors.update', $collector), [
                'collector_frequencies' => ['daily', 'weekly'],
                'collector_overdue_days' => 10,
            ])
            ->assertRedirect(route('settings.index'));

        $this->assertSame(['daily', 'weekly'], $collector->fresh()->collector_frequencies);

        $this->actingAs($admin)
            ->post(route('settings.collectors.update', $otherCollector), [
                'collector_frequencies' => ['daily'],
                'collector_overdue_days' => 5,
            ])
            ->assertNotFound();
    }

    private function tenant(string $name, string $slug, string $role): array
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

        return [$company, $this->user($company, $role)];
    }

    private function user(Company $company, string $role): User
    {
        return User::factory()->create([
            'company_id' => $company->id,
            'role' => $role,
        ]);
    }

    private function loan(Company $company): Loan
    {
        $customer = Customer::create([
            'company_id' => $company->id,
            'first_name' => 'Cliente',
            'last_name' => $company->name,
            'phone' => fake()->unique()->numerify('##########'),
            'status' => 'active',
        ]);

        return Loan::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'type' => 'interest',
            'payment_frequency' => 'daily',
            'number_of_periods' => 10,
            'original_amount' => 100,
            'remaining_balance' => 100,
            'interest_rate' => 10,
            'start_date' => now()->subDay()->toDateString(),
            'next_payment_date' => now()->toDateString(),
            'status' => 'active',
        ]);
    }
}
