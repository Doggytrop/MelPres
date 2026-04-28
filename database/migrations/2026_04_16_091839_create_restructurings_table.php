<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restructurings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('original_loan_id')
                  ->constrained('loans')
                  ->restrictOnDelete();

            $table->foreignId('new_loan_id')
                  ->nullable()
                  ->constrained('loans')
                  ->nullOnDelete();

            $table->foreignId('recorded_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->enum('type', [
                'forgiveness',
                'extension',
                'new_loan',
            ]);

            // — Forgiveness —
            $table->decimal('original_penalty', 12, 2)->default(0);
            $table->decimal('forgiven_penalty', 12, 2)->default(0);
            $table->decimal('remaining_penalty', 12, 2)->default(0);

            // — Extension —
            $table->integer('previous_periods')->nullable();
            $table->integer('new_periods')->nullable();

            // — General —
            $table->decimal('balance_at_restructuring', 12, 2);
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restructurings');
    }
};