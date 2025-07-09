<?php

namespace App\Http\Resources\Publico;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ProgramacionTurnoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'turno_id' => $this->turno_id,
            'rondero_id' =>  $this->rondero_id,
            'tipo_asistencia' => $this->tipo_asistencia, //1 = Asistencia, 2 = Inasistencia, 3 = Tardanza
            'observaciones' => $this->observaciones,
            'estado' => $this->estado,
            'nombres' => $this->rondero->persona->nombres,
            'apellido_paterno' => $this->rondero->persona->apellido_paterno,
            'apellido_materno' => $this->rondero->persona->apellido_materno,
            'docIdentidad' => $this->rondero->persona->docIdentidad,

        ];
    }
}
