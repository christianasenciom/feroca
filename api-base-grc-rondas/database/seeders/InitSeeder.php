<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Base\User;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class InitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permisosIniciales = [
            'auth.session.iniciar',
            'auth.usuarios.listar',
            'auth.usuarios.crear',
            'auth.usuarios.actualizar',
            'auth.usuarios.eliminar',
            'auth.roles.listar',
            'auth.roles.crear',
            'auth.roles.actualizar',
            'auth.roles.eliminar',
            'auth.permisos.listar',
            'auth.permisos.crear',
            'auth.permisos.actualizar',
            'auth.permisos.eliminar',
        ];

        $permisosCreados = [];
        // Creando los permisos
        foreach ($permisosIniciales as $permiso) {
            $PermisoCreado = Permission::create(['name' => $permiso, 'guard_name' => 'api']);
            array_push($permisosCreados, $PermisoCreado);
        }
        // Roles
        $adminRole = Role::create(['name' => 'SuperAdministrador',  'guard_name' => 'api']);
        $userRole = Role::create(['name' => 'Administrador', 'guard_name' => 'api']);


        // Assign permissions to roles
        $adminRole->givePermissionTo($permisosCreados);
        $userRole->givePermissionTo($permisosCreados[0]);

        $adminUser = User::create([
            'email' => 'admin@demo.gob.pe',
            'password' => Hash::make('123456789'),
        ]);

        $adminUser->assignRole($adminRole);

        // Create normal user
        $normalUser = User::create([

            'email' => 'user@demo.gob.pe',
            'password' => Hash::make('123456789'),
        ]);

        $normalUser->assignRole($userRole);

    }
}
