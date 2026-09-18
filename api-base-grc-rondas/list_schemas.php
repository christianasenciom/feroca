<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Schemas en la base de datos:\n";
$schemas = DB::select("
    SELECT schema_name
    FROM information_schema.schemata
    WHERE schema_name NOT IN ('information_schema', 'pg_catalog', 'pg_toast')
    ORDER BY schema_name
");

foreach ($schemas as $schema) {
    echo "- {$schema->schema_name}\n";
}

echo "\n\nTablas disponibles:\n";
$tables = DB::select("
    SELECT table_schema, table_name
    FROM information_schema.tables
    WHERE table_schema NOT IN ('information_schema', 'pg_catalog', 'pg_toast')
    ORDER BY table_schema, table_name
");

$currentSchema = null;
foreach ($tables as $table) {
    if ($currentSchema !== $table->table_schema) {
        echo "\n{$table->table_schema}:\n";
        $currentSchema = $table->table_schema;
    }
    echo "  - {$table->table_name}\n";
}
