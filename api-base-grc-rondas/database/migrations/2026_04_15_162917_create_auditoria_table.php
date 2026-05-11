<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_consulta'); // 'reniec' o 'requisitoriado'
            $table->string('dni_consultado')->nullable();
            $table->string('nombre_consultado')->nullable();
            $table->text('datos_consulta')->nullable(); // JSON con los datos devueltos
            $table->string('ip_usuario')->nullable();
            $table->string('user_agent')->nullable();
            
            // Relación con el usuario
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('auth.users')->onDelete('set null');
            
            // Relación con la ubicación del rondero (si aplica)
            $table->unsignedBigInteger('rondero_id')->nullable();
            $table->foreign('rondero_id')->references('id')->on('public.rondero')->onDelete('set null');
            
            $table->unsignedBigInteger('region_id')->nullable();
            $table->unsignedBigInteger('provincia_id')->nullable();
            $table->unsignedBigInteger('distrito_id')->nullable();
            $table->unsignedBigInteger('sector_zona_id')->nullable();
            $table->unsignedBigInteger('base_id')->nullable();
            
            $table->boolean('encontrado')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('auditoria');
    }
};