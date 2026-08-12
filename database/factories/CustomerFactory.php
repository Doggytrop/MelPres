<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->unique()->numerify('6#########'),
            'document_type' => 'ine',
            'document_number' => fake()->unique()->bothify('INE########??'),
            'address' => fake()->address(),
            'references' => fake()->sentence(),
            'status' => 'active',
            'notes' => null,
            'score' => 100,
            'score_updated_at' => null,
            'latitude' => null,
            'longitude' => null,
        ];
    }
}
