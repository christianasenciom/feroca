<?php

namespace App\Http\Resources\Publico;

use App\Http\Resources\Admin\Auth\PersonaResource;
use App\Http\Resources\Admin\Maestros\DepartamentoResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataCarnetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'foto' => asset('files_rondas/fotos_personas/'.$this->persona->foto),
            'nombre_completo' =>  $this->persona->apellido_paterno . ' ' . $this->persona->apellido_materno . ' ' . $this->persona->nombres,
            'dni' => $this->persona->docIdentidad, //opcional
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_cese' => $this->fecha_cese,
            'estado' =>  $this->estado,
            'cargo' => $this->comites->pluck('cargo.descripcion')->toArray(),
            'base' => $this->base->descripcion,
            'prov_dis_regi' => $this->region->descripcion . ' - ' . $this->provincia->descripcion . ' - ' . $this->distrito->descripcion,
        ];
    }
}
