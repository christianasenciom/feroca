<?php

namespace App\Http\Resources\Publico;

use App\Http\Resources\Admin\Auth\PersonaResource;
use App\Http\Resources\Publico\DetalleDenunciaResource;
use App\Http\Resources\Publico\ConflictoResource;
use App\Http\Resources\Publico\RegionResource;
use App\Http\Resources\Publico\ProvinciaResource;
use App\Http\Resources\Publico\DistritoResource;
use App\Http\Resources\Publico\SectorResource;
use App\Http\Resources\Publico\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DenunciaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //dd($this);
        return [
            'id' => $this->id,
            'descripcion' => $this->descripcion,
            'fecha' => $this->fecha,
            'denunciante_id' => new PersonaResource($this->denunciante),
            'tipo_conflicto_id' => new ConflictoResource($this->conflicto),
            'num_denuncia' => $this->num_denuncia,
            'libro' => $this->libro,
            'hoja' => $this->hoja,
            'estado' => $this->estado,
            'fecha_cita' => $this->fecha_cita,
            'observaciones' => $this->observaciones,
            'region' => new RegionResource($this->region),
            'provincia' => new ProvinciaResource($this->provincia),
            'distrito' => new DistritoResource($this->distrito),
            'sector' => new SectorResource($this->sector),
            'base' => new BaseResource($this->base),
            'estado_denuncia' => $this->estado_denuncia,
            // 'foto' =>  $this->foto,
            'listaDenunciados' =>  DetalleDenunciaResource::collection($this->listaDenunciados),
        ];
    }
}
