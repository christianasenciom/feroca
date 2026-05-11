<?php

namespace App\Http\Resources\Publico;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Obtener relaciones cargadas de forma segura
        $sector = $this->relationLoaded('sector') ? $this->sector : null;
        $distrito = $this->relationLoaded('distrito') ? $this->distrito : null;
        $provincia = $this->relationLoaded('provincia') ? $this->provincia : null;
        $region = $this->relationLoaded('region') ? $this->region : null;
        $admin = $this->relationLoaded('admin') ? $this->admin : null;

        // Datos del sector - AHORA usa 'descripcion'
        $sectorData = null;
        if ($sector) {
            $sectorData = [
                'id' => $sector->id,
                'descripcion' => $sector->descripcion, // ← CAMBIAR
                'distrito_id' => $sector->distrito_id,
            ];
        }

        // Datos del distrito
        $distritoData = null;
        if ($distrito) {
            $distritoData = [
                'id' => $distrito->id,
                'descripcion' => $distrito->descripcion,
                'provincia_id' => $distrito->provincia_id,
            ];
        }

        // Datos de la provincia
        $provinciaData = null;
        if ($provincia) {
            $provinciaData = [
                'id' => $provincia->id,
                'descripcion' => $provincia->descripcion,
                'region_id' => $provincia->region_id,
            ];
        }

        // Datos de la región
        $regionData = null;
        if ($region) {
            $regionData = [
                'id' => $region->id,
                'descripcion' => $region->descripcion,
            ];
        }

        // Datos del administrador
        $adminData = null;
        if ($admin) {
            $adminData = [
                'id' => $admin->id,
                'nombres' => $admin->nombres,
                'apellido_paterno' => $admin->apellido_paterno,
                'apellido_materno' => $admin->apellido_materno,
                'docIdentidad' => $admin->docIdentidad,
                'nombre_completo' => trim($admin->nombres . ' ' . $admin->apellido_paterno . ' ' . $admin->apellido_materno),
            ];
        }

        return [
            // Campos principales
            'id' => $this->id,
            'nombre_descripcion' => $this->nombre_descripcion,
            'numero_partida_registral' => $this->numero_partida_registral,

            // IDs de ubicación
            'region_id' => $this->region_id,
            'provincia_id' => $this->provincia_id,
            'distrito_id' => $this->distrito_id,
            'sector_zona_id' => $this->sector_zona_id,
            'admin_id' => $this->admin_id,

            // Estado y eliminación
            'estado' => (bool) $this->estado,
            'eliminado' => (bool) $this->eliminado,

            // Relaciones COMPLETAS
            'sector' => $sectorData,
            'distrito' => $distritoData,
            'provincia' => $provinciaData,
            'region' => $regionData,
            'admin' => $adminData,

            // Campos adicionales para la vista
            'descripcion' => $this->nombre_descripcion,
            'nombre' => $this->nombre_descripcion,

            // Campos formateados para display en tabla
            'sector_descripcion' => $sectorData['descripcion'] ?? null, // ← CAMBIAR
            'distrito_descripcion' => $distritoData['descripcion'] ?? null,
            'provincia_descripcion' => $provinciaData['descripcion'] ?? null,
            'region_descripcion' => $regionData['descripcion'] ?? null,

            // Campos anidados para la vista antigua - AHORA usa 'descripcion'
            'sector_zona' => $sectorData ? [
                'id' => $sectorData['id'],
                'descripcion' => $sectorData['descripcion'], // ← CAMBIAR
                'nombre' => $sectorData['descripcion'], // Mantener para compatibilidad
                'distrito' => $distritoData ? [
                    'id' => $distritoData['id'],
                    'descripcion' => $distritoData['descripcion'],
                    'nombre' => $distritoData['descripcion'], // Para compatibilidad
                    'provincia' => $provinciaData ? [
                        'id' => $provinciaData['id'],
                        'descripcion' => $provinciaData['descripcion'],
                        'region' => $regionData
                    ] : null
                ] : null
            ] : null,

            // Timestamps
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
