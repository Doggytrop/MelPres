<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {

            $table->id();

            $table->foreignId('prestamo_id')
                  ->constrained('prestamos')
                  ->restrictOnDelete();

            $table->decimal('monto_pagado', 12, 2);
            $table->decimal('abono_mora', 12, 2)->default(0);
            $table->decimal('abono_interes', 12, 2)->default(0);
            $table->decimal('abono_capital', 12, 2)->default(0);

            $table->enum('tipo_pago', [
                'capital',
                'mora',
                'solo_interes',
                'mixto',
                'parcial',
                'completo',
                'excedente',
            ]);

            $table->date('fecha_pago');
            $table->date('fecha_esperada')->nullable();

            $table->text('observaciones')->nullable();

            $table->foreignId('registrado_por')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};