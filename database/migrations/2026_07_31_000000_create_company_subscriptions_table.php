<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique();
            $table->string('status');
            $table->dateTime('started_at');
            $table->dateTime('current_period_start');
            $table->dateTime('current_period_end')->nullable();
            $table->dateTime('grace_until')->nullable();
            $table->dateTime('suspended_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->foreign('company_id', 'company_subscriptions_company_id_foreign')
                ->references('id')
                ->on('companies')
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_subscriptions');
    }
};
