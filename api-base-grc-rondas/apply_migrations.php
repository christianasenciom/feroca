<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Ejecutando migraciones manualmente...\n\n";

// 1. Crear permiso
echo "1. Creando permiso 'auth.roles.asignarpermisos'...\n";
$permissionExists = DB::table('auth.permissions')->where('name', 'auth.roles.asignarpermisos')->exists();
if (!$permissionExists) {
    DB::table('auth.permissions')->insert([
        'name' => 'auth.roles.asignarpermisos',
        'guard_name' => 'api',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo "   ✓ Permiso creado\n";
} else {
    echo "   ✓ Permiso ya existe\n";
}

// 2. Asignar permisos de rondero
echo "\n2. Asignando permisos de rondero...\n";
$permissions = [
    'pub.rondero.crear',
    'pub.rondero.actualizar',
    'pub.rondero.eliminar',
    'pub.rondero.ver'
];

foreach ($permissions as $perm) {
    $permissionExists = DB::table('auth.permissions')->where('name', $perm)->exists();
    if (!$permissionExists) {
        DB::table('auth.permissions')->insert([
            'name' => $perm,
            'guard_name' => 'api',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "   ✓ Permiso '$perm' creado\n";
    } else {
        echo "   ✓ Permiso '$perm' ya existe\n";
    }
}

// 3. Asignar permisos a SuperAdministrador y Administrador
echo "\n3. Asignando permisos a roles...\n";
$roles = ['SuperAdministrador', 'Administrador'];
foreach ($roles as $roleName) {
    $role = DB::table('auth.roles')->where('name', $roleName)->first();
    if ($role) {
        foreach ($permissions as $perm) {
            $permission = DB::table('auth.permissions')->where('name', $perm)->first();
            if ($permission) {
                $exists = DB::table('auth.role_has_permissions')
                    ->where('role_id', $role->id)
                    ->where('permission_id', $permission->id)
                    ->exists();
                if (!$exists) {
                    DB::table('auth.role_has_permissions')->insert([
                        'permission_id' => $permission->id,
                        'role_id' => $role->id
                    ]);
                    echo "   ✓ Permiso '$perm' asignado a '$roleName'\n";
                } else {
                    echo "   ✓ Permiso '$perm' ya asignado a '$roleName'\n";
                }
            }
        }
    }
}

// 4. Sincronizar roles desde cargos
echo "\n4. Sincronizando roles desde cargos...\n";
$cargos = DB::table('publico.comite')
    ->select('cargo')
    ->distinct()
    ->whereNotNull('cargo')
    ->get();

foreach ($cargos as $cargo) {
    $roleName = $cargo->cargo;
    $roleExists = DB::table('auth.roles')->where('name', $roleName)->exists();
    if (!$roleExists) {
        DB::table('auth.roles')->insert([
            'name' => $roleName,
            'guard_name' => 'api',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "   ✓ Rol '$roleName' creado\n";
    } else {
        echo "   ✓ Rol '$roleName' ya existe\n";
    }
}

// 5. Agregar columnas a tabla users
echo "\n5. Agregando columnas a tabla auth.users...\n";
if (!Schema::hasColumn('auth.users', 'name')) {
    Schema::table('auth.users', function ($table) {
        $table->string('name')->nullable()->after('email');
    });
    echo "   ✓ Columna 'name' agregada\n";
} else {
    echo "   ✓ Columna 'name' ya existe\n";
}

if (!Schema::hasColumn('auth.users', 'persona_id')) {
    Schema::table('auth.users', function ($table) {
        $table->unsignedBigInteger('persona_id')->nullable()->after('name');
        $table->foreign('persona_id')->references('id')->on('publico.personas')->onDelete('cascade');
    });
    echo "   ✓ Columna 'persona_id' agregada\n";
} else {
    echo "   ✓ Columna 'persona_id' ya existe\n";
}

if (!Schema::hasColumn('auth.users', 'cambioPassword')) {
    Schema::table('auth.users', function ($table) {
        $table->boolean('cambioPassword')->default(false)->after('persona_id');
    });
    echo "   ✓ Columna 'cambioPassword' agregada\n";
} else {
    echo "   ✓ Columna 'cambioPassword' ya existe\n";
}

if (!Schema::hasColumn('auth.users', 'eliminado')) {
    Schema::table('auth.users', function ($table) {
        $table->boolean('eliminado')->default(false)->after('cambioPassword');
    });
    echo "   ✓ Columna 'eliminado' agregada\n";
} else {
    echo "   ✓ Columna 'eliminado' ya existe\n";
}

echo "\n✅ Todas las migraciones se ejecutaron correctamente!\n";
