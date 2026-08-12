<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'company_id' => fn (array $attributes) => Loan::query()
                ->findOrFail($attributes['loan_id'])
                ->company_id,
            'recorded_by' => fn (array $attributes) => User::factory()
                ->for(Company::query()->findOrFail($attributes['company_id']))
                ->create()
                ->getKey(),
            'amount_paid' => 500,
            'penalty_payment' => 0,
            'interest_payment' => 0,
            'capital_payment' => 500,
            'payment_date' => now()->startOfDay(),
            'expected_date' => now()->startOfDay(),
            'payment_type' => 'capital',
            'notes' => null,
            'periods_covered' => 1,
            'carry_over' => 0,
        ];
    }
}
