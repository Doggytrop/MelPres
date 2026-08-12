<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use App\Services\CompanyAutomationEligibility;
use App\Services\CompanyContext;
use App\Services\PenaltyService;
use App\Services\ScoreService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class AutomationSubscriptionPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_shared_eligibility_covers_every_subscription_and_company_state_without_n_plus_one(): void
    {
        Carbon::setTestNow('2026-07-31 12:00:00');

        $expected = [
            $this->company(SubscriptionStatus::ACTIVE)->id => true,
            $this->company(SubscriptionStatus::PAST_DUE, now()->addMinute())->id => true,
            $this->company(SubscriptionStatus::PAST_DUE, now()->subMinute())->id => false,
            $this->company(SubscriptionStatus::PAST_DUE)->id => false,
            $this->company(SubscriptionStatus::SUSPENDED)->id => false,
            $this->company(SubscriptionStatus::CANCELLED)->id => false,
            $this->company(null)->id => false,
            $this->company(SubscriptionStatus::ACTIVE, null, 'inactive')->id => false,
        ];

        DB::flushQueryLog();
        DB::enableQueryLog();
        $eligibility = app(CompanyAutomationEligibility::class);
        $companies = $eligibility->companies();

        foreach ($expected as $companyId => $allowed) {
            $company = $companies->firstWhere('id', $companyId);
            $this->assertNotNull($company);
            $this->assertTrue($company->relationLoaded('subscription'));
            $this->assertSame($allowed, $eligibility->allows($company));
        }

        $this->assertCount(2, DB::getQueryLog());
        DB::disableQueryLog();
    }

    public function test_process_penalties_only_processes_eligible_company_loans(): void
    {
        $eligible = $this->company(SubscriptionStatus::ACTIVE);
        $suspended = $this->company(SubscriptionStatus::SUSPENDED);
        $eligibleLoan = $this->loan($eligible);
        $suspendedLoan = $this->loan($suspended);

        $this->mock(PenaltyService::class, function (MockInterface $mock) use ($eligibleLoan, $suspendedLoan): void {
            $mock->shouldReceive('processLoan')
                ->once()
                ->withArgs(fn (Loan $loan): bool => $loan->is($eligibleLoan));
            $mock->shouldNotReceive('processLoan')->with($suspendedLoan);
        });

        $this->mock(WhatsAppService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('sendOverdueAlert');
        });

        $this->artisan('loans:process-penalties')
            ->expectsOutputToContain('Procesadas:')
            ->expectsOutputToContain('Omitidas por política comercial:')
            ->assertExitCode(Command::SUCCESS);

        $this->assertNull(app(CompanyContext::class)->getCompany());
    }

    public function test_send_reminders_only_sends_for_eligible_companies(): void
    {
        $eligible = $this->company(SubscriptionStatus::PAST_DUE, now()->addDay());
        $cancelled = $this->company(SubscriptionStatus::CANCELLED);
        $eligibleLoan = $this->loan($eligible, now()->addDay());
        $cancelledLoan = $this->loan($cancelled, now()->addDay());

        $this->mock(WhatsAppService::class, function (MockInterface $mock) use ($eligibleLoan, $cancelledLoan): void {
            $mock->shouldReceive('sendPaymentReminder')
                ->once()
                ->withArgs(fn (Customer $customer, Loan $loan): bool => $loan->is($eligibleLoan))
                ->andReturnTrue();
            $mock->shouldNotReceive('sendPaymentReminder')
                ->withArgs(fn (Customer $customer, Loan $loan): bool => $loan->is($cancelledLoan));
        });

        $this->artisan('loans:send-reminders')
            ->assertExitCode(Command::SUCCESS);

        $this->assertNull(app(CompanyContext::class)->getCompany());
    }

    public function test_recalculate_scores_processes_mixed_companies_according_to_policy(): void
    {
        $activeCustomer = $this->customer($this->company(SubscriptionStatus::ACTIVE));
        $graceCustomer = $this->customer($this->company(SubscriptionStatus::PAST_DUE, now()->addHour()));
        $expiredCustomer = $this->customer($this->company(SubscriptionStatus::PAST_DUE, now()->subHour()));
        $missingCustomer = $this->customer($this->company(null));
        $inactiveCustomer = $this->customer($this->company(SubscriptionStatus::ACTIVE, null, 'inactive'));

        $eligibleIds = [$activeCustomer->id, $graceCustomer->id];

        $this->mock(ScoreService::class, function (MockInterface $mock) use ($eligibleIds): void {
            $mock->shouldReceive('actualizar')
                ->twice()
                ->withArgs(fn (Customer $customer): bool => in_array($customer->id, $eligibleIds, true))
                ->andReturnUsing(function (Customer $customer): void {
                    $customer->update(['score' => 777]);
                });
        });

        $this->artisan('customers:recalcular-scores')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame(777, $activeCustomer->fresh()->score);
        $this->assertSame(777, $graceCustomer->fresh()->score);
        $this->assertNotSame(777, $expiredCustomer->fresh()->score);
        $this->assertNotSame(777, $missingCustomer->fresh()->score);
        $this->assertNotSame(777, $inactiveCustomer->fresh()->score);
    }

    public function test_omitted_company_business_data_remains_unchanged(): void
    {
        $company = $this->company(SubscriptionStatus::SUSPENDED);
        $admin = User::factory()->for($company)->create(['role' => 'admin']);
        $customer = $this->customer($company);
        $loan = Loan::factory()->for($customer)->create([
            'next_payment_date' => now()->addDay()->toDateString(),
        ]);
        $payment = Payment::factory()->for($loan)->create([
            'company_id' => $company->id,
            'recorded_by' => $admin->id,
        ]);
        $before = [
            'customer' => DB::table('customers')->where('id', $customer->id)->first(),
            'loan' => DB::table('loans')->where('id', $loan->id)->first(),
            'payment' => DB::table('payments')->where('id', $payment->id)->first(),
        ];

        $this->mock(PenaltyService::class, fn (MockInterface $mock) => $mock->shouldNotReceive('processLoan'));
        $this->mock(WhatsAppService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('sendPaymentReminder');
            $mock->shouldNotReceive('sendOverdueAlert');
        });
        $this->mock(ScoreService::class, fn (MockInterface $mock) => $mock->shouldNotReceive('actualizar'));

        $this->artisan('loans:process-penalties')->assertExitCode(Command::SUCCESS);
        $this->artisan('loans:send-reminders')->assertExitCode(Command::SUCCESS);
        $this->artisan('customers:recalcular-scores')->assertExitCode(Command::SUCCESS);

        $this->assertEquals(
            $before['customer'],
            DB::table('customers')->where('id', $customer->id)->first()
        );
        $this->assertEquals(
            $before['loan'],
            DB::table('loans')->where('id', $loan->id)->first()
        );
        $this->assertEquals(
            $before['payment'],
            DB::table('payments')->where('id', $payment->id)->first()
        );
    }

    public function test_exception_clears_context_and_does_not_contaminate_next_company(): void
    {
        $firstCustomer = $this->customer($this->company(SubscriptionStatus::ACTIVE));
        $secondCustomer = $this->customer($this->company(SubscriptionStatus::ACTIVE));
        $context = app(CompanyContext::class);
        $visited = [];

        $this->mock(ScoreService::class, function (MockInterface $mock) use (
            $firstCustomer,
            $secondCustomer,
            $context,
            &$visited
        ): void {
            $mock->shouldReceive('actualizar')
                ->twice()
                ->andReturnUsing(function (Customer $customer) use (
                    $firstCustomer,
                    $secondCustomer,
                    $context,
                    &$visited
                ): void {
                    $visited[$customer->id] = $context->getCompanyId();

                    if ($customer->is($firstCustomer)) {
                        throw new RuntimeException('Forced company failure');
                    }

                    $this->assertTrue($customer->is($secondCustomer));
                    $customer->update(['score' => 888]);
                });
        });

        $this->artisan('customers:recalcular-scores')
            ->expectsOutputToContain('Errores: 1')
            ->assertExitCode(Command::FAILURE);

        $this->assertSame($firstCustomer->company_id, $visited[$firstCustomer->id]);
        $this->assertSame($secondCustomer->company_id, $visited[$secondCustomer->id]);
        $this->assertSame(888, $secondCustomer->fresh()->score);
        $this->assertNull($context->getCompany());
    }

    public function test_scheduler_frequencies_remain_unchanged(): void
    {
        $schedule = file_get_contents(base_path('routes/console.php'));

        $this->assertStringContainsString(
            "Schedule::command('loans:process-penalties')->dailyAt('00:05');",
            $schedule
        );
        $this->assertStringContainsString(
            "Schedule::command('loans:send-reminders')->dailyAt('09:00');",
            $schedule
        );
    }

    private function company(
        ?SubscriptionStatus $subscriptionStatus,
        mixed $graceUntil = null,
        string $companyStatus = 'active'
    ): Company {
        $company = Company::factory()->create(['status' => $companyStatus]);

        if ($subscriptionStatus !== null) {
            $subscription = CompanySubscription::create([
                'company_id' => $company->id,
                'status' => $subscriptionStatus,
                'started_at' => now(),
                'current_period_start' => now(),
                'grace_until' => $graceUntil,
                'suspended_at' => $subscriptionStatus === SubscriptionStatus::SUSPENDED ? now() : null,
                'cancelled_at' => $subscriptionStatus === SubscriptionStatus::CANCELLED ? now() : null,
            ]);
            $company->setRelation('subscription', $subscription);
        }

        return $company;
    }

    private function customer(Company $company): Customer
    {
        return Customer::factory()->for($company)->create();
    }

    private function loan(Company $company, mixed $nextPaymentDate = null): Loan
    {
        return Loan::factory()
            ->for($this->customer($company))
            ->create([
                'next_payment_date' => $nextPaymentDate ?? now()->subDays(10)->toDateString(),
                'grace_days' => 0,
            ]);
    }
}
