<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamos', function (Blueprint $table) {

            $table->id();

            // — Relación —
            $table->foreignId('cliente_id')
                  ->constrained('clientes')
                  ->restrictOnDelete();

            // — Tipo y configuración —
            $table->enum('tipo', ['interes', 'plazo']);
            $table->enum('frecuencia_pago', ['semanal', 'quincenal', 'mensual']);
            $table->unsignedTinyInteger('numero_periodos')->nullable();

            // — Montos —
            $table->decimal('monto_original', 12, 2);
            $table->decimal('saldo_restante', 12, 2);
            $table->decimal('interes_rate', 5, 2);
            $table->decimal('interes_acumulado', 12, 2)->default(0);

            // — Mora —
            $table->enum('mora_tipo', ['fija', 'porcentaje'])->nullable();
            $table->decimal('mora_valor', 10, 2)->nullable();
            $table->unsignedTinyInteger('dias_gracia')->default(0);
            $table->decimal('mora_acumulada', 12, 2)->default(0);

            // — Fechas —
            $table->date('fecha_inicio');
            $table->date('fecha_vencimiento')->nullable();
            $table->date('fecha_proximo_pago')->nullable();

            // — Estado —
            $table->enum('estado', ['activo', 'pagado', 'vencido', 'refinanciado'])
                  ->default('activo');

            // — Notas —
            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};