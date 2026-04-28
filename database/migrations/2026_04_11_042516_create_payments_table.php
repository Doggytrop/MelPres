<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('loan_id')
                  ->constrained('loans')
                  ->restrictOnDelete();

            $table->decimal('amount_paid', 12, 2);
            $table->decimal('penalty_payment', 12, 2)->default(0);
            $table->decimal('interest_payment', 12, 2)->default(0);
            $table->decimal('capital_payment', 12, 2)->default(0);

            $table->enum('payment_type', [
                'capital',
                'penalty',
                'interest_only',
                'mixed',
                'partial',
                'complete',
                'excess',
            ]);

            $table->date('payment_date');
            $table->date('expected_date')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('recorded_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};