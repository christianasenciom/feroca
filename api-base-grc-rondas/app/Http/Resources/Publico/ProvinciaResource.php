<?php

namespace App\Http\Resources\Publico;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ProvinciaResource extends JsonResource
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
            'descripcion' => $this->descripcion,
            // 'region_id' => $this->region_id,
            'region' => new RegionResource($this->region),
            'estado' =>  $this->estado,
            'eliminado' =>  $this->eliminado,
        ];
    }
}
