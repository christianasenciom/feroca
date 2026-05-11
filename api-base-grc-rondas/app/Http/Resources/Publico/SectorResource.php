<?php

namespace App\Http\Resources\Publico;

use App\Models\Publico\Distrito;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class SectorResource extends JsonResource
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
            'distrito' => new DistritoResource($this->distrito),
            'estado' =>  $this->estado,
        ];
    }
}
