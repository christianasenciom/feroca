<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRonderoTable extends Migration
{
    /**
     * Run the migrations.
     * Esta migración es solo para registrar en Laravel que el cambio se hizo
     * El cambio real (renombrar partida_registral a codigo_rondero) ya se ejecutó manualmente
     */
    public function up()
    {
        // No hacemos nada aquí porque ya ejecutamos el SQL manualmente
        // Solo nos aseguramos que la columna tenga las propiedades correctas
        
        Schema::table('public.rondero', function (Blueprint $table) {
            // Asegurar que la columna codigo_rondero existe y tiene el tipo correcto
            // Esto es solo por si alguien ejecuta la migración desde cero
            if (!Schema::hasColumn('public.rondero', 'codigo_rondero')) {
                $table->string('codigo_rondero', 8)->nullable()->after('base_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // No revertimos automáticamente porque el rename fue manual
        // Si necesitas revertir, deberás ejecutar manualmente:
        // ALTER TABLE public.rondero RENAME COLUMN codigo_rondero TO partida_registral;
        
        Schema::table('public.rondero', function (Blueprint $table) {
            // Solo eliminamos índices si existen
            $indexes = [
                'rondero_codigo_rondero_unique',
                'rondero_codigo_rondero_index',
                'public.rondero_codigo_rondero_unique',
                'public.rondero_codigo_rondero_index'
            ];
            
            foreach ($indexes as $index) {
                if (Schema::hasIndex('public.rondero', $index)) {
                    $table->dropIndex($index);
                }
            }
        });
    }
}