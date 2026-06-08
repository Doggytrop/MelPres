<?php
// database/migrations/2026_06_03_000000_update_penalty_type_enum.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Convertir registros 'percentage' existentes antes de cambiar el enum
        DB::statement("UPDATE loans SET penalty_type = 'percentage_period' WHERE penalty_type = 'percentage'");
        DB::statement("ALTER TABLE loans MODIFY COLUMN penalty_type ENUM('fixed','percentage_period','percentage_balance') NULL");
        DB::statement("ALTER TABLE loans ADD COLUMN penalty_last_applied_date DATE NULL AFTER accumulated_penalty");
    }

    public function down(): void
    {
        DB::statement("UPDATE loans SET penalty_type = 'percentage' WHERE penalty_type IN ('percentage_period','percentage_balance')");
        DB::statement("ALTER TABLE loans MODIFY COLUMN penalty_type ENUM('fixed','percentage') NULL");
        DB::statement("ALTER TABLE loans DROP COLUMN penalty_last_applied_date");
    }
};