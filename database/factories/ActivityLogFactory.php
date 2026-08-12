<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_id' => fn (array $attributes) => User::query()
                ->findOrFail($attributes['user_id'])
                ->company_id,
            'user_name' => fn (array $attributes) => User::query()
                ->findOrFail($attributes['user_id'])
                ->name,
            'action' => 'create',
            'module' => 'testing',
            'description' => fake()->sentence(),
            'model_type' => null,
            'model_id' => null,
            'old_values' => null,
            'new_values' => null,
            'ip_address' => '127.0.0.1',
        ];
    }

    public function withoutUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => Company::factory(),
            'user_id' => null,
            'user_name' => 'Sistema',
        ]);
    }
}
