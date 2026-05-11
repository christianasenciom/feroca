<?php
// database/migrations/[timestamp]_update_base_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBaseTable extends Migration
{
    public function up()
    {
        Schema::table('public.base', function (Blueprint $table) {
            // 1. Agregar partida_registral si no existe
            if (!Schema::hasColumn('public.base', 'partida_registral')) {
                $table->string('partida_registral', 8)->nullable()->after('nombre');
            } else {
                // Si ya existe, asegurarse que tenga 8 caracteres
                $table->string('partida_registral', 8)->nullable()->change();
            }
            
            // 2. Agregar distrito_id si no existe
            if (!Schema::hasColumn('public.base', 'distrito_id')) {
                $table->foreignId('distrito_id')->nullable()->after('sector_zona_id')
                      ->constrained('public.distrito')->nullOnDelete();
            }
            
            // 3. Asegurar que sector_zona_id sea nullable
            $table->foreignId('sector_zona_id')->nullable()->change();
            
            // 4. Agregar índices para mejor performance
            $table->index('partida_registral');
            $table->index(['distrito_id', 'sector_zona_id']);
            
            // 5. Verificar constraint: al menos uno de los dos debe estar presente
            // Esto se puede hacer a nivel de aplicación o con trigger
        });
    }

    public function down()
    {
        Schema::table('public.base', function (Blueprint $table) {
            // Eliminar índices
            $table->dropIndex(['partida_registral']);
            $table->dropIndex(['distrito_id', 'sector_zona_id']);
            
            // Eliminar columna distrito_id
            if (Schema::hasColumn('public.base', 'distrito_id')) {
                $table->dropForeign(['distrito_id']);
                $table->dropColumn('distrito_id');
            }
            
            // No eliminamos partida_registral para no perder datos
            // Si quieres revertir completamente, descomenta la siguiente línea:
            // $table->dropColumn('partida_registral');
        });
    }
}