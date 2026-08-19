<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use App\Services\LoanPaymentStateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentComponentPresentationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_and_cash_register_use_persisted_interest_and_penalty_for_the_active_tenant_and_month(): void
    {
        Carbon::setTestNow('2026-08-19 12:00:00');
        [$company, $admin, $loan] = $this->tenantLoan();

        $this->payment($loan, $admin, 180, 100, 50, 30, '2026-08-19');
        $this->payment($loan, $admin, 100, 100, 0, 0, '2026-07-31');

        [$otherCompany, $otherAdmin, $otherLoan] = $this->tenantLoan();
        $this->payment($otherLoan, $otherAdmin, 1_998, 0, 999, 999, '2026-08-19');

        $dashboard = $this->actingAs($admin)->get(route('dashboard'));

        $dashboard->assertOk()
            ->assertViewHas('interestDelMes', fn ($amount) => (float) $amount === 50.0)
            ->assertViewHas('moraDelMes', fn ($amount) => (float) $amount === 30.0);

        $cash = $this->actingAs($admin)->get(route('cash-register.index', ['fecha' => '2026-08-19']));

        $cash->assertOk()
            ->assertSee('Interés: $50.00')
            ->assertSee('Mora: $30.00')
            ->assertDontSee('$999.00');

        $payments = Payment::query()
            ->where('company_id', $company->id)
            ->whereDate('payment_date', '2026-08-19')
            ->with(['loan.customer', 'recordedBy'])
            ->get();

        $pdf = view('cash-register.pdf', [
            'payments' => $payments,
            'poradvisor' => $payments->groupBy('recorded_by'),
            'totalCobrado' => $payments->sum('amount_paid'),
            'totalCapital' => $payments->sum('capital_payment'),
            'totalinterest' => $payments->sum('interest_payment'),
            'totalMora' => $payments->sum('penalty_payment'),
            'fecha' => '2026-08-19',
        ])->render();

        $this->assertStringContainsString('$50.00', $pdf);
        $this->assertStringContainsString('$30.00', $pdf);
    }

    public function test_administrative_history_labels_the_total_as_monto_and_only_renders_persisted_positive_components(): void
    {
        [$company, $admin, $loan] = $this->tenantLoan();
        foreach ([
            [100, 100, 0, 0, ['Capital: $100.00'], ['Interés:', 'Mora:']],
            [150, 100, 50, 0, ['Capital: $100.00', 'Interés: $50.00'], ['Mora:']],
            [130, 100, 0, 30, ['Capital: $100.00', 'Mora: $30.00'], ['Interés:']],
            [180, 100, 50, 30, ['Capital: $100.00', 'Interés: $50.00', 'Mora: $30.00'], []],
        ] as [$amount, $capital, $interest, $penalty, $visibleComponents, $hiddenComponents]) {
            $caseLoan = $loan->fresh();
            $payment = $this->payment($caseLoan, $admin, $amount, $capital, $interest, $penalty, '2026-08-19');
            $caseLoan->setRelation('payments', collect([$payment]));

            $html = view('loans._payments', [
                'loan' => $caseLoan,
                'paymentState' => app(LoanPaymentStateService::class)->state($caseLoan),
            ])->render();

            $this->assertStringContainsString('Monto: $'.number_format($amount, 2), $html);

            foreach ($visibleComponents as $component) {
                $this->assertStringContainsString($component, $html);
            }

            foreach ($hiddenComponents as $component) {
                $this->assertStringNotContainsString($component, $html);
            }
        }

        $legacyLoan = $this->loanFor($company);
        $legacy = $this->payment($legacyLoan, $admin, 100, 0, 0, 0, '2026-08-19');
        $legacyLoan->setRelation('payments', collect([$legacy]));

        $legacyHtml = view('loans._payments', [
            'loan' => $legacyLoan,
            'paymentState' => app(LoanPaymentStateService::class)->state($legacyLoan),
        ])->render();

        $this->assertStringContainsString('Monto: $100.00', $legacyHtml);
        $this->assertStringNotContainsString('Capital:', $legacyHtml);
        $this->assertStringNotContainsString('Interés:', $legacyHtml);
        $this->assertStringNotContainsString('Mora:', $legacyHtml);
    }

    private function tenantLoan(): array
    {
        $company = Company::factory()->create();
        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => SubscriptionStatus::ACTIVE,
            'started_at' => now(),
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $admin = User::factory()->for($company)->create(['role' => 'admin']);

        return [$company, $admin, $this->loanFor($company)];
    }

    private function loanFor(Company $company): Loan
    {
        $customer = Customer::factory()->for($company)->create();

        return Loan::factory()->for($customer)->create([
            'company_id' => $company->id,
            'next_payment_date' => '2026-08-19',
        ]);
    }

    private function payment(Loan $loan, User $user, float $amount, float $capital, float $interest, float $penalty, string $date): Payment
    {
        return Payment::create([
            'company_id' => $loan->company_id,
            'loan_id' => $loan->id,
            'recorded_by' => $user->id,
            'amount_paid' => $amount,
            'capital_payment' => $capital,
            'interest_payment' => $interest,
            'penalty_payment' => $penalty,
            'payment_date' => $date,
            'payment_type' => 'mixed',
            'periods_covered' => 1,
        ]);
    }
}
