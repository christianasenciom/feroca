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
        Schema::table('rondero', function (Blueprint $table) {
            $table->string('partida_registral', 100)->nullable()->after('base_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rondero', function (Blueprint $table) {
            $table->dropColumn('partida_registral');
        });
    }
};