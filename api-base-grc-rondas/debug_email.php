<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Buscar personas con email "mpalma@regioncajamarca.gob.pe"
echo "Personas con email 'mpalma@regioncajamarca.gob.pe':\n";
$personas = DB::table('publico.personas')
    ->where('email', 'mpalma@regioncajamarca.gob.pe')
    ->get();

foreach ($personas as $p) {
    echo "ID: {$p->id}, DOC: {$p->docIdentidad}, Email: {$p->email}\n";
}

// Buscar usuarios con email "mpalma@regioncajamarca.gob.pe"
echo "\nUsuarios con email 'mpalma@regioncajamarca.gob.pe':\n";
$users = DB::table('auth.users')
    ->where('email', 'mpalma@regioncajamarca.gob.pe')
    ->get();

foreach ($users as $u) {
    echo "ID: {$u->id}, Name: {$u->name}, Email: {$u->email}, Persona ID: {$u->persona_id}\n";
}

// Ver si hay restricción unique en publico.personas.email
echo "\n\nEstructura de tabla publico.personas:\n";
$columns = DB::select("
    SELECT column_name, data_type, is_nullable
    FROM information_schema.columns
    WHERE table_schema = 'publico' AND table_name = 'personas'
    ORDER BY ordinal_position
");

foreach ($columns as $col) {
    echo "- {$col->column_name} ({$col->data_type}, nullable: {$col->is_nullable})\n";
}

// Ver índices
echo "\n\nÍndices en publico.personas:\n";
$indexes = DB::select("
    SELECT indexname, indexdef
    FROM pg_indexes
    WHERE tablename = 'personas' AND schemaname = 'publico'
");

foreach ($indexes as $idx) {
    echo "- {$idx->indexname}\n";
    echo "  {$idx->indexdef}\n";
}
