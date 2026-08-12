<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaymentActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_payment_creates_exactly_one_canonical_activity_log(): void
    {
        $company = Company::factory()->create(['status' => 'active']);
        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'started_at' => now(),
            'current_period_start' => now(),
        ]);
        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
        ]);
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'phone' => null,
        ]);
        $loan = Loan::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'remaining_balance' => 100,
            'pending_interest' => 0,
            'accumulated_penalty' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('loans.payments.store', $loan), [
                'amount_paid' => 25,
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('loans.show', $loan));

        $logs = ActivityLog::where('company_id', $company->id)
            ->where('module', 'payments')
            ->where('model_type', Loan::class)
            ->where('model_id', $loan->id)
            ->get();

        $this->assertCount(1, $logs);
        $this->assertSame('payment', $logs->sole()->action);
        $this->assertSame(
            'Registró pago por $25.00 en préstamo #'.$loan->id,
            $logs->sole()->description
        );
    }
}
