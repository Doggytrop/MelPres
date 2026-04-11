<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // database/migrations/xxxx_create_clientes_table.php

public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {

            $table->id();

            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('telefono', 20)->nullable();
            $table->string('dui', 20)->nullable()->unique(); // Documento de identidad
            $table->text('direccion')->nullable();
            $table->text('referencias')->nullable(); // Quién lo recomienda, referencias personales
            $table->enum('estado', ['activo', 'inactivo', 'bloqueado'])->default('activo');
            $table->text('notas')->nullable(); // Condiciones especiales, historial informal

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};