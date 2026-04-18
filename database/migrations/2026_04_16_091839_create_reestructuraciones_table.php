<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reestructuraciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('prestamo_original_id')
                  ->constrained('prestamos')
                  ->restrictOnDelete();

            $table->foreignId('prestamo_nuevo_id')
                  ->nullable()
                  ->constrained('prestamos')
                  ->nullOnDelete();

            $table->foreignId('registrado_por')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->enum('tipo', [
                'condonacion',  // Se perdona parte de la mora
                'extension',    // Se extiende el plazo
                'nuevo_prestamo' // Se cierra y crea uno nuevo
            ]);

            // Condonación
            $table->decimal('mora_original', 12, 2)->default(0);
            $table->decimal('mora_condonada', 12, 2)->default(0);
            $table->decimal('mora_restante', 12, 2)->default(0);

            // Extensión
            $table->integer('periodos_anteriores')->nullable();
            $table->integer('periodos_nuevos')->nullable();

            // General
            $table->decimal('saldo_al_reestructurar', 12, 2);
            $table->text('motivo')->nullable();
            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reestructuraciones');
    }
};