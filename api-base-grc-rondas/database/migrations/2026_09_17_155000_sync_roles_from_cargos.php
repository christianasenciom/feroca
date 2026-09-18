<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\Publico\Comite;
use App\Models\Publico\Rondero;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sincronizar roles basados en cargos existentes
        $comites = Comite::with(['rondero.persona.user', 'cargo'])
            ->where('eliminado', false)
            ->whereDate('fecha_inicio', '<=', now())
            ->where(function ($q) {
                $q->whereNull('fecha_fin')
                  ->orWhereDate('fecha_fin', '>=', now());
            })
            ->get();

        foreach ($comites as $comite) {
            try {
                if ($comite->cargo && $comite->rondero && $comite->rondero->persona) {
                    $user = $comite->rondero->persona->user;
                    if ($user) {
                        // Obtener el rol por el nombre del cargo
                        $role = Role::where('name', $comite->cargo->descripcion)->first();
                        if ($role && !$user->hasRole($role->name)) {
                            $user->assignRole($role);
                            \Log::info("Rol '{$role->name}' asignado a usuario ID {$user->id}");
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error("Error sincronizando rol para comité ID {$comite->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se revierte para no afectar datos existentes
    }
};
