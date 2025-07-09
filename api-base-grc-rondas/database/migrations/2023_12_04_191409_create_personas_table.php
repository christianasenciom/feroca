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
        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->enum('documento_tipo', ['CE', 'DNI', 'RUC'])->comment("CE = Carnet extranjeria");
            $table->string('docIdentidad');
            $table->string('apellido_paterno')->nullable();
            $table->string('apellido_materno')->nullable();
            $table->string('nombres');
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('genero', ['MASCULINO', 'FEMENINO'])->nullable()->comment("Obligatorio persona natural");
            $table->string('celular')->nullable();
            $table->string('direccion')->nullable();
            $table->string('email')->nullable();
            $table->enum('tipo', ['Natural', 'Juridica']);
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
