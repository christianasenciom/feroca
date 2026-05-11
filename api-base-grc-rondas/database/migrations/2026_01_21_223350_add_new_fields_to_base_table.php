<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Primero verifica si la tabla existe y si ya tiene los campos
        if (Schema::hasTable('public.base')) {
            
            // Agregar campo nombre_descripcion si no existe (en lugar de nombre/descripcion)
            if (!Schema::hasColumn('public.base', 'nombre_descripcion')) {
                Schema::table('public.base', function (Blueprint $table) {
                    $table->string('nombre_descripcion')->nullable()->after('id');
                });
            }
            
            // Renombrar partida_registral a numero_partida_registral si existe
            if (Schema::hasColumn('public.base', 'partida_registral') && !Schema::hasColumn('public.base', 'numero_partida_registral')) {
                Schema::table('public.base', function (Blueprint $table) {
                    $table->renameColumn('partida_registral', 'numero_partida_registral');
                });
            }
            
            // Agregar numero_partida_registral si no existe
            if (!Schema::hasColumn('public.base', 'numero_partida_registral')) {
                Schema::table('public.base', function (Blueprint $table) {
                    $table->string('numero_partida_registral', 8)->nullable()->after('nombre_descripcion');
                });
            }
            
            // Agregar region_id
            if (!Schema::hasColumn('public.base', 'region_id')) {
                Schema::table('public.base', function (Blueprint $table) {
                    $table->unsignedBigInteger('region_id')->nullable()->after('numero_partida_registral');
                });
            }
            
            // Agregar provincia_id
            if (!Schema::hasColumn('public.base', 'provincia_id')) {
                Schema::table('public.base', function (Blueprint $table) {
                    $table->unsignedBigInteger('provincia_id')->nullable()->after('region_id');
                });
            }
            
            // Asegurarse que distrito_id existe (ya debería existir)
            if (!Schema::hasColumn('public.base', 'distrito_id')) {
                Schema::table('public.base', function (Blueprint $table) {
                    $table->unsignedBigInteger('distrito_id')->nullable()->after('provincia_id');
                });
            }
            
            // Asegurarse que sector_zona_id existe (ya debería existir)
            if (!Schema::hasColumn('public.base', 'sector_zona_id')) {
                Schema::table('public.base', function (Blueprint $table) {
                    $table->unsignedBigInteger('sector_zona_id')->nullable()->after('distrito_id');
                });
            }
            
            // Agregar admin_id si no existe
            if (!Schema::hasColumn('public.base', 'admin_id')) {
                Schema::table('public.base', function (Blueprint $table) {
                    $table->unsignedBigInteger('admin_id')->nullable()->after('sector_zona_id');
                });
            }
            
            // Agregar estado si no existe
            if (!Schema::hasColumn('public.base', 'estado')) {
                Schema::table('public.base', function (Blueprint $table) {
                    $table->boolean('estado')->default(true)->after('admin_id');
                });
            }
            
            // Agregar eliminado si no existe
            if (!Schema::hasColumn('public.base', 'eliminado')) {
                Schema::table('public.base', function (Blueprint $table) {
                    $table->boolean('eliminado')->default(false)->after('estado');
                });
            }
            
            // Agregar foreign keys
            Schema::table('public.base', function (Blueprint $table) {
                // Solo agregar foreign keys si no existen
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('public.base');
                
                if (!isset($indexes['base_region_id_foreign'])) {
                    $table->foreign('region_id')->references('id')->on('public.region')->onDelete('set null');
                }
                
                if (!isset($indexes['base_provincia_id_foreign'])) {
                    $table->foreign('provincia_id')->references('id')->on('public.provincia')->onDelete('set null');
                }
                
                if (!isset($indexes['base_distrito_id_foreign'])) {
                    $table->foreign('distrito_id')->references('id')->on('public.distrito')->onDelete('set null');
                }
                
                if (!isset($indexes['base_sector_zona_id_foreign'])) {
                    $table->foreign('sector_zona_id')->references('id')->on('public.sector_zona')->onDelete('set null');
                }
                
                // Índices para mejor rendimiento
                $table->index(['region_id', 'provincia_id', 'distrito_id', 'sector_zona_id'], 'base_ubicacion_index');
                $table->index('numero_partida_registral');
            });
            
            // Copiar datos de campos antiguos a nuevos si es necesario
            if (Schema::hasColumn('public.base', 'nombre')) {
                DB::statement('UPDATE public.base SET nombre_descripcion = nombre WHERE nombre_descripcion IS NULL AND nombre IS NOT NULL');
            }
            if (Schema::hasColumn('public.base', 'descripcion') && !Schema::hasColumn('public.base', 'nombre')) {
                DB::statement('UPDATE public.base SET nombre_descripcion = descripcion WHERE nombre_descripcion IS NULL AND descripcion IS NOT NULL');
            }
        }
    }

    public function down()
    {
        // No hacemos rollback completo para evitar pérdida de datos
        // Solo removemos las nuevas foreign keys
        Schema::table('public.base', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('public.base');
            
            if (isset($indexes['base_region_id_foreign'])) {
                $table->dropForeign(['region_id']);
            }
            
            if (isset($indexes['base_provincia_id_foreign'])) {
                $table->dropForeign(['provincia_id']);
            }
            
            if (isset($indexes['base_distrito_id_foreign'])) {
                $table->dropForeign(['distrito_id']);
            }
            
            if (isset($indexes['base_sector_zona_id_foreign'])) {
                $table->dropForeign(['sector_zona_id']);
            }
            
            // Remover índices
            $table->dropIndex('base_ubicacion_index');
            $table->dropIndex('base_numero_partida_registral_index');
        });
    }
};  