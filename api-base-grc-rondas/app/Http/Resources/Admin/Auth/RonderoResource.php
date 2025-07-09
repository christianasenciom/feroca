<?php

namespace App\Http\Resources\Admin\Auth;

use App\Http\Resources\GlobalResource;
use App\Http\Resources\Publico\BaseResource;
use App\Http\Resources\Publico\CargoResource;
use App\Http\Resources\Publico\ComiteResource;
use App\Http\Resources\Publico\DistritoResource;
use App\Http\Resources\Publico\ProvinciaResource;
use App\Http\Resources\Publico\RegionResource;
use App\Http\Resources\Publico\SectorResource;
use App\Models\Publico\Provincia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RonderoResource extends JsonResource
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
            'persona' => new PersonaResource($this->persona),
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_cese' => $this->fecha_cese,
            'codigo_qr' =>  $this->codigo_qr,
            'estado' =>  $this->estado,
            'region' => new RegionResource($this->region),
            'provincia' => new ProvinciaResource($this->provincia),
            'distrito' => new DistritoResource($this->distrito),
            'sector' => new SectorResource($this->sector_zona),
            'base' => new BaseResource($this->base),
            'cargos' => ComiteResource::collection($this->comites),
            'partida_registral' => $this->partida_registral,
        ];
    }
}
