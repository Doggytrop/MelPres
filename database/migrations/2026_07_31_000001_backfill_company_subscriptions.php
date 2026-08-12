<?php

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();

        DB::table('companies')
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($companies) use ($timestamp): void {
                foreach ($companies as $company) {
                    $exists = DB::table('company_subscriptions')
                        ->where('company_id', $company->id)
                        ->exists();

                    if (! $exists) {
                        DB::table('company_subscriptions')->insert([
                            'company_id' => $company->id,
                            'status' => SubscriptionStatus::ACTIVE->value,
                            'started_at' => $timestamp,
                            'current_period_start' => $timestamp,
                            'current_period_end' => null,
                            'grace_until' => null,
                            'suspended_at' => null,
                            'cancelled_at' => null,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Subscription records may have changed after backfill; rollback must not delete business data.
    }
};
