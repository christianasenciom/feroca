<?php

namespace App\Http\Resources\Publico;

use App\Http\Resources\Publico\DenunciadoResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetalleDenunciaResource extends JsonResource
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
            'denuncia_id' => $this->denuncia_id,
            'observaciones' => $this->observaciones,
            'denunciado' => new DenunciadoResource($this->denunciado),
        ];
    }
}
