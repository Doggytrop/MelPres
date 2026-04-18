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
            Schema::table('clientes', function (Blueprint $table) {
                $table->enum('documento_tipo', [
                    'ine',
                    'pasaporte',
                    'cedula',
                    'licencia',
                    'otro',
                ])->nullable()->after('apellido');

                $table->string('documento_numero', 50)->nullable()->after('documento_tipo');

                $table->dropColumn('dui');
            });
        }

        public function down(): void
        {
            Schema::table('clientes', function (Blueprint $table) {
                $table->string('dui', 20)->nullable()->unique();
                $table->dropColumn(['documento_tipo', 'documento_numero']);
            });
        }
};
