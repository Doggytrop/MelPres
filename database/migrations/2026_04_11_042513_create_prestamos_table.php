<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        // database/migrations/xxxx_create_prestamos_table.php

    public function up(): void
    {
        Schema::create('prestamos', function (Blueprint $table) {

            $table->id();

            // — Relación —
            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->restrictOnDelete(); // No borrar cliente con préstamos activos

            // — Tipo y configuración —
            $table->enum('tipo', ['interes', 'plazo']);
            // 'interes' = solo paga intereses, capital no baja
            // 'plazo'   = capital + interés, tiene fecha fin

            $table->enum('frecuencia_pago', ['semanal', 'quincenal', 'mensual']);

            // — Montos —
            $table->decimal('monto_original', 12, 2);   // Lo que se prestó
            $table->decimal('saldo_restante', 12, 2);   // Lo que falta pagar (capital)
            $table->decimal('interes_rate', 5, 2);      // % mensual, ej: 8.00
            $table->decimal('interes_acumulado', 12, 2)->default(0); // Solo tipo 'plazo'

            // — Mora —
            $table->enum('mora_tipo', ['fija', 'porcentaje'])->nullable();
            $table->decimal('mora_valor', 10, 2)->nullable(); // Monto fijo o % diario
            $table->unsignedTinyInteger('dias_gracia')->default(0);
            $table->decimal('mora_acumulada', 12, 2)->default(0);

            // — Fechas —
            $table->date('fecha_inicio');
            $table->date('fecha_vencimiento')->nullable(); // Solo tipo 'plazo'
            $table->date('fecha_proximo_pago')->nullable(); // Calculado al registrar pago

            // — Estado —
            $table->enum('estado', ['activo', 'pagado', 'vencido', 'refinanciado'])
                ->default('activo');

            // — Notas —
            $table->text('observaciones')->nullable(); // Condiciones especiales del cliente

            $table->timestamps();
            $table->softDeletes(); // Nunca borrar préstamos, solo archivar
        });
    }

        public function down(): void
        {
            Schema::dropIfExists('prestamos');
        }
    };