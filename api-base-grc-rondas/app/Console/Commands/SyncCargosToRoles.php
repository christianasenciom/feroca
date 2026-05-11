<?php

namespace App\Console\Commands;

use App\Models\Publico\Cargo;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncCargosToRoles extends Command
{
    protected $signature = 'sync:cargos-to-roles';
    protected $description = 'Sincroniza los cargos de la tabla cargos con la tabla roles de Spatie';

    public function handle()
    {
        $this->info('=== Sincronizando Cargos a Roles ===');
        
        // Obtener todos los cargos activos
        $cargos = Cargo::where('estado', true)->get();
        
        $this->info("Se encontraron {$cargos->count()} cargos para sincronizar");
        
        $created = 0;
        $updated = 0;
        
        foreach ($cargos as $cargo) {
            // Normalizar el nombre del rol (mayúsculas, sin espacios)
            $rolName = strtoupper(trim(preg_replace('/\s+/', '_', $cargo->descripcion)));
            
            // Buscar o crear el rol
            $role = Role::where('name', $rolName)->first();
            
            if (!$role) {
                $role = Role::create([
                    'name' => $rolName,
                    'guard_name' => 'api',
                    'created_by' => 1
                ]);
                $created++;
                $this->info("✅ Rol creado: {$rolName}");
            } else {
                $updated++;
                $this->info("ℹ️ Rol ya existe: {$rolName}");
            }
            
            // Asignar permisos básicos para este rol
            $this->asignarPermisosBasicos($role);
        }
        
        $this->info("=== Sincronización completada ===");
        $this->info("Roles creados: {$created}");
        $this->info("Roles actualizados: {$updated}");
        
        return Command::SUCCESS;
    }
    
    private function asignarPermisosBasicos($role)
    {
        // Permisos básicos para cualquier rol de rondero
        $basicPermissions = [
            'pub.rondero.listar',
            'pub.rondero.ver',
            'pub.rondero.actualizar',
            'pub.rondero.carnet',
        ];
        
        foreach ($basicPermissions as $permName) {
            $permission = Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'api'
            ]);
            
            if (!$role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }
}