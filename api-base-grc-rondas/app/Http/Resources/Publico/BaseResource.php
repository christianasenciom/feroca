<?php

namespace App\Http\Resources\Publico;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class BaseResource extends JsonResource
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
            'sector_zona' => new SectorResource($this->sector),
            'estado' =>  $this->estado,
            'admin_id' =>  $this->admin_id,
        ];
    }
}
