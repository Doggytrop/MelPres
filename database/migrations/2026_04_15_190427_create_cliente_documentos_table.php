<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_documentos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cliente_id')
                  ->constrained('clientes')
                  ->cascadeOnDelete();

            $table->enum('tipo', [
                'foto_perfil',
                'ine_frente',
                'ine_reverso',
                'comprobante_domicilio',
                'nomina',
                'otro',
            ]);

            $table->string('nombre_original'); // nombre del archivo original
            $table->string('ruta');            // ruta en storage
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('tamanio')->nullable(); // bytes
            $table->text('notas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_documentos');
    }
};