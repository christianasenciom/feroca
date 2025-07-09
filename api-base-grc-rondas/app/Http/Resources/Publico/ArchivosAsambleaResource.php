<?php

namespace App\Http\Resources\Publico;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ArchivosAsambleaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
//        return parent::toArray($request);
        return [
            'id' => $this->id,
            'ruta_archivo' => asset('files_rondas/'.$this->ruta_archivo),
            'turno_id' => $this->turno_id,
            'name' => $this->ruta_archivo,
            'url' => asset('files_rondas/'.$this->ruta_archivo),
        ];
    }
}
