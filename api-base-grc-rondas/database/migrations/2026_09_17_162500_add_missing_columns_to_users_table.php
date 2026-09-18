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
        Schema::table('users', function (Blueprint $table) {
            // Agregar columnas faltantes si no existen
            if (!Schema::hasColumn('users', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            
            if (!Schema::hasColumn('users', 'persona_id')) {
                $table->unsignedBigInteger('persona_id')->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('users', 'cambioPassword')) {
                $table->boolean('cambioPassword')->default(false)->after('password');
            }
            
            if (!Schema::hasColumn('users', 'eliminado')) {
                $table->boolean('eliminado')->default(false)->after('cambioPassword');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['name', 'persona_id', 'cambioPassword', 'eliminado']);
        });
    }
};
