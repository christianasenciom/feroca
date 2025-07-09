<?php

namespace App\Http\Resources\Publico;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DenunciadoResource extends JsonResource
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
            // 'persona_id' => $this->id,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'nombres' => $this->nombres,
            'nombre_completo' =>  $this->apellido_paterno . ' ' . $this->apellido_materno . ' ' . $this->nombres,
            // 'docIdentidad' => $this->docIdentidad,
            'direccion' => $this->direccion,
            'celular' => $this->celular,
            'email' => $this->email
        ];
    }
}
