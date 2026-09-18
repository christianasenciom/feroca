<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if permission already exists
        $exists = DB::table('auth.permissions')->where('name', 'auth.roles.asignarpermisos')->exists();
        
        if (!$exists) {
            DB::table('auth.permissions')->insert([
                'name' => 'auth.roles.asignarpermisos',
                'guard_name' => 'api',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Assign permission to SuperAdministrador and Administrador roles
        $superAdminRole = DB::table('auth.roles')->where('name', 'SuperAdministrador')->first();
        $adminRole = DB::table('auth.roles')->where('name', 'Administrador')->first();
        $permiso = DB::table('auth.permissions')->where('name', 'auth.roles.asignarpermisos')->first();

        if ($permiso) {
            if ($superAdminRole) {
                $existe_asignacion = DB::table('auth.role_has_permissions')
                    ->where('role_id', $superAdminRole->id)
                    ->where('permission_id', $permiso->id)
                    ->exists();
                
                if (!$existe_asignacion) {
                    DB::table('auth.role_has_permissions')->insert([
                        'role_id' => $superAdminRole->id,
                        'permission_id' => $permiso->id
                    ]);
                }
            }

            if ($adminRole) {
                $existe_asignacion = DB::table('auth.role_has_permissions')
                    ->where('role_id', $adminRole->id)
                    ->where('permission_id', $permiso->id)
                    ->exists();
                
                if (!$existe_asignacion) {
                    DB::table('auth.role_has_permissions')->insert([
                        'role_id' => $adminRole->id,
                        'permission_id' => $permiso->id
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('auth.permissions')->where('name', 'auth.roles.asignarpermisos')->delete();
    }
};
