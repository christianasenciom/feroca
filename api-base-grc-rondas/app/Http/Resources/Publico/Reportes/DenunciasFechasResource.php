<?php

namespace App\Http\Resources\Publico\Reportes;

use App\Http\Resources\Publico\DetalleDenunciaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DenunciasFechasResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'fecha' => date('d/m/Y', strtotime($this->fecha)),
            'tipo_conflicto' => $this->conflicto->descripcion,
            'num_denuncia' => $this->num_denuncia,
            'denunciante' => $this->denunciante->nombres . ' ' . $this->denunciante->apellido_paterno . ' ' . $this->denunciante->apellido_materno,
            'listaDenunciados' =>  DetalleDenunciaResource::collection($this->listaDenunciados),
            'estado_denuncia' => $this->estado_denuncia,
            'descripcion' => $this->descripcion,
        ];
    }
}
