<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
        */
        public function up(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            $table->decimal('interes_pendiente', 12, 2)->default(0)->after('interes_acumulado');
        });
    }

    public function down(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropColumn('interes_pendiente');
        });
    }
};
