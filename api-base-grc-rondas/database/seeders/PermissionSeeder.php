<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Base\User;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Limpiar cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Lista de permisos de auditoría
        $permisosAuditoria = [
            'admin.auditoria.ver',
            'admin.auditoria.listar',
            'admin.auditoria.exportar',
        ];

        // Crear permisos
        foreach ($permisosAuditoria as $permiso) {
            Permission::firstOrCreate([
                'name' => $permiso,
                'guard_name' => 'api'
            ]);
            echo "✅ Permiso creado: {$permiso}\n";
        }

        // Obtener roles
        $rolSuperAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'api']);
        $rolAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $rolUser = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

        // ASIGNAR PERMISOS DE AUDITORÍA SOLO A super-admin Y admin
        // 1. Remover permisos de auditoría de todos los roles primero
        foreach ($permisosAuditoria as $permiso) {
            $rolUser->revokePermissionTo($permiso);
        }
        
        // 2. Asignar permisos solo a super-admin y admin
        foreach ($permisosAuditoria as $permiso) {
            $rolSuperAdmin->givePermissionTo($permiso);
            $rolAdmin->givePermissionTo($permiso);
            echo "✅ Permiso '{$permiso}' asignado a super-admin y admin\n";
        }

        // Asignar todos los permisos al superadmin (ID 1)
        $superadmin = User::find(1);
        if ($superadmin) {
            $superadmin->syncPermissions(Permission::all());
            echo "✅ Todos los permisos asignados al usuario superadmin (ID: 1)\n";
        } else {
            echo "⚠️ Usuario con ID 1 no encontrado\n";
        }

        // Asignar permisos de auditoría al admin (ID 2, si existe)
        $admin = User::find(2);
        if ($admin) {
            foreach ($permisosAuditoria as $permiso) {
                $admin->givePermissionTo($permiso);
            }
            echo "✅ Permisos de auditoría asignados al usuario admin (ID: 2)\n";
        }

        echo "\n✅ RESUMEN:\n";
        echo "   - Super-admin: TIENE acceso a auditoría\n";
        echo "   - Admin: TIENE acceso a auditoría\n";
        echo "   - User/Rol normal: NO TIENE acceso a auditoría\n";
    }
}