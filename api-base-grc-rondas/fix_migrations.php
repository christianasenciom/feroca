<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Get migrations table
$migrations = DB::table('migrations')->get();
echo "Current migrations in DB:\n";
foreach ($migrations as $m) {
    echo "ID: {$m->id}, Migration: {$m->migration}, Batch: {$m->batch}\n";
}

echo "\n\nPending migrations to register:\n";
$pending = [
    '2026_09_17_150200_create_assign_permissions_role_permission',
    '2026_09_17_152000_assign_rondero_permissions',
    '2026_09_17_155000_sync_roles_from_cargos',
    '2026_09_17_162500_add_missing_columns_to_users_table',
];

foreach ($pending as $migration) {
    echo "- $migration\n";
    
    // Check if already registered
    $exists = DB::table('migrations')
        ->where('migration', $migration)
        ->exists();
    
    if (!$exists) {
        DB::table('migrations')->insert([
            'migration' => $migration,
            'batch' => 6
        ]);
        echo "  ✓ Registered\n";
    } else {
        echo "  ✗ Already registered\n";
    }
}

echo "\nDone!\n";
