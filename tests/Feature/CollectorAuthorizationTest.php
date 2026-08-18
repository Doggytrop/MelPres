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

    public function test_pending_list_uses_today_and_overdue_badges_by_open_obligation_date(): void
    {
        [$company, $collector] = $this->tenant('Empresa Uno', 'collector-badges', 'collector');
        $this->dailyLoan($company, now()->toDateString(), 0, 'Hoy');
        $graceLoan = $this->dailyLoan($company, now()->subDay()->toDateString(), 3, 'Gracia Visual');
        $this->dailyLoan($company, now()->subDays(3)->toDateString(), 1, 'Atrasado');

        $response = $this->actingAs($collector)->get(route('collector.index'));

        $response->assertOk()
            ->assertSee('Pendientes (3)')
            ->assertSee('HOY')
            ->assertSee('ATRASADO')
            ->assertDontSee('EN GRACIA');

        $this->assertSame('0.00', $graceLoan->fresh()->accumulated_penalty);
    }

    public function test_collected_today_keeps_local_midnight_payments_and_tenant_isolation(): void
    {
        \Carbon\Carbon::setTestNow(
            \Carbon\Carbon::parse('2026-08-16 00:15:00', config('app.timezone'))
        );

        try {
            [$company, $collector] = $this->tenant('Empresa Uno', 'collector-local-midnight', 'collector');
            $loan = $this->dailyLoan($company, now()->toDateString(), 0, 'Parciales Medianoche');
            $otherCollector = $this->user($company, 'collector');
            [$otherCompany, $otherTenantCollector] = $this->tenant('Empresa Dos', 'collector-local-midnight-other', 'collector');
            $otherLoan = $this->dailyLoan($otherCompany, now()->toDateString(), 0, 'Ajeno Medianoche');

            Payment::create([
                'company_id' => $company->id,
                'loan_id' => $loan->id,
                'amount_paid' => 60,
                'payment_date' => now()->toDateString(),
                'payment_type' => 'capital',
                'recorded_by' => $collector->id,
            ]);
            Payment::create([
                'company_id' => $company->id,
                'loan_id' => $loan->id,
                'amount_paid' => 40,
                'payment_date' => now()->toDateString(),
                'payment_type' => 'capital',
                'recorded_by' => $collector->id,
            ]);
            Payment::create([
                'company_id' => $company->id,
                'loan_id' => $loan->id,
                'amount_paid' => 70,
                'payment_date' => now()->toDateString(),
                'payment_type' => 'capital',
                'recorded_by' => $otherCollector->id,
            ]);
            Payment::create([
                'company_id' => $otherCompany->id,
                'loan_id' => $otherLoan->id,
                'amount_paid' => 99,
                'payment_date' => now()->toDateString(),
                'payment_type' => 'capital',
                'recorded_by' => $otherTenantCollector->id,
            ]);

            $this->actingAs($collector)->get(route('collector.index'))
                ->assertOk()
                ->assertSee('Cobrados hoy (2)')
                ->assertSee('60.00')
                ->assertSee('40.00')
                ->assertDontSee('70.00')
                ->assertDontSee('99.00');
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_partial_payment_remains_pending_and_second_payment_completes_it(): void
    {
        [$company, $collector] = $this->tenant('Empresa Uno', 'collector-partials', 'collector');
        $loan = $this->dailyLoan($company, now()->toDateString(), 0, 'Parcial');

        $this->actingAs($collector)
            ->post(route('collector.collect', $loan), ['amount_paid' => 60])
            ->assertRedirect();

        $this->actingAs($collector)->get(route('collector.index'))
            ->assertOk()
            ->assertSee('Pendientes (1)')
            ->assertSee('Pendiente de cuota: $40.00')
            ->assertSee('Cobrados hoy (1)');

        $this->actingAs($collector)
            ->post(route('collector.collect', $loan->fresh()), ['amount_paid' => 40])
            ->assertRedirect();

        $this->actingAs($collector)->get(route('collector.index'))
            ->assertOk()
            ->assertSee('Pendientes (0)')
            ->assertSee('Cobrados hoy (2)');

        $this->assertSame(2, Payment::where('loan_id', $loan->id)->count());
    }

    public function test_partial_payment_from_previous_date_remains_visually_overdue_without_creating_penalty(): void
    {
        [$company, $collector] = $this->tenant('Empresa Uno', 'collector-overdue-partial', 'collector');
        $loan = $this->dailyLoan($company, now()->subDay()->toDateString(), 3, 'Parcial Atrasado');

        $this->actingAs($collector)
            ->post(route('collector.collect', $loan), ['amount_paid' => 60])
            ->assertRedirect();

        $this->actingAs($collector)->get(route('collector.index'))
            ->assertOk()
            ->assertSee('ATRASADO')
            ->assertSee('Pendiente de cuota: $40.00');

        $this->assertSame('0.00', $loan->fresh()->accumulated_penalty);
    }

    public function test_map_uses_today_and_overdue_markers_and_keeps_google_directions_link(): void
    {
        [$company, $collector] = $this->tenant('Empresa Uno', 'collector-map-statuses', 'collector');
        $this->dailyLoan($company, now()->toDateString(), 0, 'Mapa Hoy', 29.0729673, -110.9559192);
        $this->dailyLoan($company, now()->subDay()->toDateString(), 3, 'Mapa Atrasado', 29.0829673, -110.9659192);

        $response = $this->actingAs($collector)->get(route('collector.index'));

        $response->assertOk()
            ->assertSee('Mapa de cobros')
            ->assertSee('Hoy')
            ->assertSee('Atrasado')
            ->assertDontSee('En gracia')
            ->assertSee('{ icon: greenIcon }', false)
            ->assertSee('{ icon: redIcon }', false)
            ->assertSee('https://www.google.com/maps/dir/?api=1&amp;destination=29.0729673,-110.9559192', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertSee('Ir →');
    }

    public function test_customer_address_is_visible_in_pending_card_and_map_popup(): void
    {
        [$company, $collector] = $this->tenant('Empresa Uno', 'collector-address', 'collector');
        $this->dailyLoan($company, now()->toDateString(), 0, 'Con Direccion', 29.0729673, -110.9559192, 'Calle Sonora 303, Centro');

        $response = $this->actingAs($collector)->get(route('collector.index'));

        $response->assertOk()
            ->assertSee('collector-customer-address', false)
            ->assertSee('Calle Sonora 303, Centro')
            ->assertSee('collector-popup-address', false)
            ->assertSee('collector-card-directions', false)
            ->assertSee('Ir →');

        $directionsUrl = 'https://www.google.com/maps/dir/?api=1&amp;destination=29.0729673,-110.9559192';
        $this->assertSame(2, substr_count($response->getContent(), $directionsUrl));
    }

    public function test_customer_without_address_keeps_card_and_map_working_without_empty_address_line(): void
    {
        [$company, $collector] = $this->tenant('Empresa Uno', 'collector-no-address', 'collector');
        $this->dailyLoan($company, now()->toDateString(), 0, 'Sin Direccion', 29.0729673, -110.9559192);

        $response = $this->actingAs($collector)->get(route('collector.index'));

        $response->assertOk()
            ->assertSee('Cliente Sin Direccion')
            ->assertDontSee('collector-customer-address', false)
            ->assertSee('collector-customer-location', false)
            ->assertSee('collector-card-directions', false)
            ->assertSee(".address\n                    ?", false)
            ->assertSee('Ir →');
    }

    public function test_customer_with_address_without_coordinates_has_no_card_directions_button(): void
    {
        [$company, $collector] = $this->tenant('Empresa Uno', 'collector-address-only', 'collector');
        $this->dailyLoan($company, now()->toDateString(), 0, 'Solo Direccion', null, null, 'Calle Sin Coordenadas 20');

        $this->actingAs($collector)->get(route('collector.index'))
            ->assertOk()
            ->assertSee('collector-customer-location', false)
            ->assertSee('collector-customer-address', false)
            ->assertSee('Calle Sin Coordenadas 20')
            ->assertDontSee('collector-card-directions', false);
    }

    public function test_customer_without_address_or_coordinates_has_no_location_block(): void
    {
        [$company, $collector] = $this->tenant('Empresa Uno', 'collector-no-location', 'collector');
        $this->dailyLoan($company, now()->toDateString(), 0, 'Sin Ubicacion');

        $this->actingAs($collector)->get(route('collector.index'))
            ->assertOk()
            ->assertSee('Cliente Sin Ubicacion')
            ->assertDontSee('collector-customer-location', false)
            ->assertDontSee('collector-card-directions', false);
    }

    public function test_collector_never_sees_customer_address_from_another_tenant(): void
    {
        [$company, $collector] = $this->tenant('Empresa Uno', 'collector-own-address', 'collector');
        $this->dailyLoan($company, now()->toDateString(), 0, 'Propio', null, null, 'Calle Propia 10');
        [$otherCompany] = $this->tenant('Empresa Dos', 'collector-other-address', 'admin');
        $this->dailyLoan($otherCompany, now()->toDateString(), 0, 'Ajeno', 29.0829673, -110.9659192, 'Direccion Secreta Ajena 999');

        $this->actingAs($collector)->get(route('collector.index'))
            ->assertOk()
            ->assertSee('Calle Propia 10')
            ->assertDontSee('Direccion Secreta Ajena 999')
            ->assertDontSee('29.0829673')
            ->assertDontSee('-110.9659192');
    }

    public function test_three_overdue_periods_render_as_one_loan_card(): void
    {
        [$company, $collector] = $this->tenant('Empresa Uno', 'collector-three-overdue', 'collector');
        $loan = $this->dailyLoan($company, now()->subDays(2)->toDateString(), 0, 'Tres Vencidas');

        $response = $this->actingAs($collector)->get(route('collector.index'));

        $response->assertOk()
            ->assertSee('Pendientes (1)')
            ->assertSee('3 cuotas vencidas')
            ->assertSee('Pendiente vencido: $300.00');
        $this->assertSame(1, substr_count($response->getContent(), 'Préstamo #'.$loan->id.' ·'));
    }

    public function test_debt_older_than_collector_overdue_days_remains_visible_and_collectible(): void
    {
        [$company, $collector] = $this->tenant('Empresa Uno', 'collector-old-debt', 'collector');
        $collector->update(['collector_overdue_days' => 1]);
        $loan = $this->dailyLoan($company, now()->subDays(60)->toDateString(), 0, 'Deuda Antigua');

        $this->actingAs($collector)->get(route('collector.index'))
            ->assertOk()
            ->assertSee('Deuda Antigua')
            ->assertSee('ATRASADO');

        $this->actingAs($collector)
            ->post(route('collector.collect', $loan), ['amount_paid' => 100])
            ->assertRedirect();

        $this->assertDatabaseHas('payments', ['loan_id' => $loan->id, 'amount_paid' => 100]);
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

    private function dailyLoan(
        Company $company,
        string $nextDate,
        int $graceDays,
        string $lastName,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $address = null
    ): Loan
    {
        $customer = Customer::create([
            'company_id' => $company->id,
            'first_name' => 'Cliente',
            'last_name' => $lastName,
            'status' => 'active',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'address' => $address,
        ]);

        return Loan::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'type' => 'daily',
            'payment_frequency' => 'daily',
            'number_of_periods' => 100,
            'original_amount' => 10000,
            'remaining_balance' => 10000,
            'interest_rate' => 0,
            'accrued_interest' => 0,
            'pending_interest' => 0,
            'daily_payment' => 100,
            'accumulated_penalty' => 0,
            'grace_days' => $graceDays,
            'start_date' => now()->subDays(70)->toDateString(),
            'due_date' => now()->addDays(100)->toDateString(),
            'next_payment_date' => $nextDate,
            'status' => 'active',
        ]);
    }
}
