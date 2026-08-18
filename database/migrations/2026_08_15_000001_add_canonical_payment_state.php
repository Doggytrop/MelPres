<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->decimal('current_period_balance', 12, 2)->nullable()->after('daily_payment');
            $table->decimal('payment_credit', 12, 2)->default(0)->after('current_period_balance');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('periodic_amount_applied', 12, 2)->default(0)->after('capital_payment');
            $table->decimal('credit_generated', 12, 2)->default(0)->after('carry_over');
            $table->decimal('credit_consumed', 12, 2)->default(0)->after('credit_generated');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['periodic_amount_applied', 'credit_generated', 'credit_consumed']);
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['current_period_balance', 'payment_credit']);
        });
    }
};
