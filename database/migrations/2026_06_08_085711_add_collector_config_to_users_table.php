<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('collector_frequencies')->nullable()->after('customer_id');
            $table->unsignedTinyInteger('collector_overdue_days')->default(15)->after('collector_frequencies');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['collector_frequencies', 'collector_overdue_days']);
        });
    }
};