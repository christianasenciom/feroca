<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Restricciones UNIQUE en tabla auth.users:\n";
$constraints = DB::select("
    SELECT constraint_name, column_name
    FROM information_schema.constraint_column_usage
    WHERE table_schema = 'auth' 
    AND table_name = 'users'
    AND constraint_name LIKE '%unique%'
");

foreach ($constraints as $c) {
    echo "- {$c->constraint_name} en columna {$c->column_name}\n";
}

// Buscar constraint específico
echo "\n\nVerificando si existe restricción email en users:\n";
$emailUnique = DB::select("
    SELECT constraint_name
    FROM information_schema.table_constraints
    WHERE table_schema = 'auth' 
    AND table_name = 'users'
    AND constraint_type = 'UNIQUE'
    AND constraint_name LIKE '%email%'
");

if ($emailUnique) {
    foreach ($emailUnique as $c) {
        echo "Encontrada: {$c->constraint_name}\n";
        echo "Removiendo...\n";
        DB::statement("ALTER TABLE auth.users DROP CONSTRAINT {$c->constraint_name}");
        echo "✓ Removida\n";
    }
} else {
    echo "No hay restricción UNIQUE en email\n";
}
