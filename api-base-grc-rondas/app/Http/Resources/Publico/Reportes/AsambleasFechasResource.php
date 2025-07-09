<?php

namespace App\Http\Resources\Publico\Reportes;

use App\Http\Resources\Publico\ArchivosAsambleaResource;
use App\Http\Resources\Publico\ProgramacionTurnoResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AsambleasFechasResource extends JsonResource
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
            'fecha' => date('d/m/Y', strtotime($this->fecha)),
            'base' =>  $this->base->descripcion,
//            'detalle_ronderos' =>  ProgramacionTurnoResource::collection($this->listaRonderos),
//            'archivos_asamblea' =>  ArchivosAsambleaResource::collection($this->archivos_asamblea),
        ];
    }
}
