<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

echo "Agregando columnas faltantes a tabla auth.users...\n\n";

// Verificar estructura actual
echo "Estructura actual de auth.users:\n";
$columns = Schema::getColumns('auth.users');
foreach ($columns as $col) {
    echo "  - {$col['name']} ({$col['type']})\n";
}

echo "\n";

// Agregar columnas faltantes
$columnsToAdd = ['name', 'persona_id', 'cambioPassword', 'eliminado'];

Schema::table('auth.users', function (Blueprint $table) use ($columnsToAdd) {
    if (!Schema::hasColumn('auth.users', 'name')) {
        $table->string('name')->nullable()->after('email');
        echo "✓ Columna 'name' agregada\n";
    }
    
    if (!Schema::hasColumn('auth.users', 'persona_id')) {
        $table->unsignedBigInteger('persona_id')->nullable()->after('name');
        echo "✓ Columna 'persona_id' agregada\n";
    }
    
    if (!Schema::hasColumn('auth.users', 'cambioPassword')) {
        $table->boolean('cambioPassword')->default(false)->after('persona_id');
        echo "✓ Columna 'cambioPassword' agregada\n";
    }
    
    if (!Schema::hasColumn('auth.users', 'eliminado')) {
        $table->boolean('eliminado')->default(false)->after('cambioPassword');
        echo "✓ Columna 'eliminado' agregada\n";
    }
});

echo "\nEstructura actualizada de auth.users:\n";
$columns = Schema::getColumns('auth.users');
foreach ($columns as $col) {
    echo "  - {$col['name']} ({$col['type']})\n";
}

echo "\n✅ ¡Columnas agregadas correctamente!\n";
