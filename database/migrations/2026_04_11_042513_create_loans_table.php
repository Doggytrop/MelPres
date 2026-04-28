<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {

            $table->id();

            // — Relationship —
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->restrictOnDelete();

            // — Type & configuration —
            $table->enum('type', ['interest', 'term']);
            $table->enum('payment_frequency', ['weekly', 'biweekly', 'monthly']);
            $table->unsignedTinyInteger('number_of_periods')->nullable();

            // — Amounts —
            $table->decimal('original_amount', 12, 2);
            $table->decimal('remaining_balance', 12, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->decimal('accrued_interest', 12, 2)->default(0);
            $table->decimal('pending_interest', 12, 2)->default(0);

            // — Penalty —
            $table->enum('penalty_type', ['fixed', 'percentage'])->nullable();
            $table->decimal('penalty_value', 10, 2)->nullable();
            $table->unsignedTinyInteger('grace_days')->default(0);
            $table->decimal('accumulated_penalty', 12, 2)->default(0);

            // — Dates —
            $table->date('start_date');
            $table->date('due_date')->nullable();
            $table->date('next_payment_date')->nullable();

            // — Status —
            $table->enum('status', ['active', 'paid', 'overdue', 'refinanced'])
                  ->default('active');

            // — Flags —
            $table->boolean('restructured')->default(false);

            // — Notes —
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};