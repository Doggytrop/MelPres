<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // database/migrations/xxxx_create_pagos_table.php

public function up(): void
{
    Schema::create('pagos', function (Blueprint $table) {

        $table->id();

        $table->foreignId('prestamo_id')
              ->constrained('prestamos')
              ->restrictOnDelete();

        $table->decimal('monto_pagado', 12, 2);       // Lo que realmente pagó
        $table->decimal('abono_mora', 12, 2)->default(0);      // Parte que fue a mora
        $table->decimal('abono_interes', 12, 2)->default(0);   // Parte que fue a interés
        $table->decimal('abono_capital', 12, 2)->default(0);   // Parte que fue a capital

        $table->date('fecha_pago');
        $table->date('fecha_esperada')->nullable(); // Cuándo debió pagar (para calcular mora)

        $table->enum('tipo_pago', [
            'completo',   // Cubrió todo lo esperado
            'parcial',    // Pagó menos de lo esperado
            'excedente',  // Pagó más (el extra va a capital)
            'solo_interes', // Solo cubrió interés, capital intacto
        ]);

        $table->text('observaciones')->nullable();
        $table->foreignId('registrado_por')->constrained('users'); // Quién lo registró

        $table->timestamps();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};