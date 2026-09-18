<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear permisos de ronderos si no existen
        $permisos = [
            'pub.rondero.crear',
            'pub.rondero.actualizar',
            'pub.rondero.eliminar',
            'pub.rondero.ver',
        ];

        foreach ($permisos as $permiso) {
            $existe = DB::table('auth.permissions')->where('name', $permiso)->exists();
            if (!$existe) {
                DB::table('auth.permissions')->insert([
                    'name' => $permiso,
                    'guard_name' => 'api',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // Obtener IDs de roles y permisos
        $superAdminRole = DB::table('auth.roles')->where('name', 'SuperAdministrador')->first();
        $adminRole = DB::table('auth.roles')->where('name', 'Administrador')->first();

        if ($superAdminRole) {
            foreach ($permisos as $permiso) {
                $permiso_obj = DB::table('auth.permissions')->where('name', $permiso)->first();
                if ($permiso_obj) {
                    // Evitar duplicados
                    $existe_asignacion = DB::table('auth.role_has_permissions')
                        ->where('role_id', $superAdminRole->id)
                        ->where('permission_id', $permiso_obj->id)
                        ->exists();
                    
                    if (!$existe_asignacion) {
                        DB::table('auth.role_has_permissions')->insert([
                            'role_id' => $superAdminRole->id,
                            'permission_id' => $permiso_obj->id
                        ]);
                    }
                }
            }
        }

        if ($adminRole) {
            foreach ($permisos as $permiso) {
                $permiso_obj = DB::table('auth.permissions')->where('name', $permiso)->first();
                if ($permiso_obj) {
                    // Evitar duplicados
                    $existe_asignacion = DB::table('auth.role_has_permissions')
                        ->where('role_id', $adminRole->id)
                        ->where('permission_id', $permiso_obj->id)
                        ->exists();
                    
                    if (!$existe_asignacion) {
                        DB::table('auth.role_has_permissions')->insert([
                            'role_id' => $adminRole->id,
                            'permission_id' => $permiso_obj->id
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('auth.permissions')->whereIn('name', [
            'pub.rondero.crear',
            'pub.rondero.actualizar',
            'pub.rondero.eliminar',
            'pub.rondero.ver',
        ])->delete();
    }
};
