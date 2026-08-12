<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        $startDate = now()->startOfDay();
        $amount = fake()->randomFloat(2, 5000, 50000);

        return [
            'customer_id' => Customer::factory(),
            'company_id' => fn (array $attributes) => Customer::query()
                ->findOrFail($attributes['customer_id'])
                ->company_id,
            'type' => 'term',
            'payment_frequency' => 'weekly',
            'number_of_periods' => 12,
            'original_amount' => $amount,
            'remaining_balance' => $amount,
            'interest_rate' => 10,
            'accrued_interest' => 0,
            'pending_interest' => 0,
            'daily_payment' => null,
            'penalty_type' => 'fixed',
            'penalty_value' => 50,
            'grace_days' => 3,
            'accumulated_penalty' => 0,
            'start_date' => $startDate,
            'due_date' => $startDate->copy()->addWeeks(12),
            'next_payment_date' => $startDate->copy()->addWeek(),
            'status' => 'active',
            'restructured' => false,
            'notes' => null,
            'penalty_last_applied_date' => null,
        ];
    }
}
