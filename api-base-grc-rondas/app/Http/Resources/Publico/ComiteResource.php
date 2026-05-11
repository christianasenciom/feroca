<?php

namespace App\Http\Resources\Publico;

use App\Http\Controllers\Publico\ComiteController;
use App\Http\Resources\GlobalResource;
use App\Models\Publico\Comite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ComiteResource extends JsonResource
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
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' =>  $this->fecha_fin,
            'cargo' => new CargoResource($this->cargo),
            'comiteable' => $this->getComitesByRondero($this->id),
        ];
    }

    public function getComitesByRondero($id)
    {
        // Obtenemos todos los comités asociados a un rondero concreto
        // con sus relaciones de rondero, cargo y comiteable
        $comites = Comite::select('comite.id','comite.cargo_id','comite.comiteable_id','comite.comiteable_type', 'comite.fecha_inicio', 'comite.fecha_fin')
            ->with(['cargo','comiteable'])
            ->where('comite.id', $id)
            ->where('eliminado', 0)
            ->get();
        // Iteramos sobre cada comité y modificamos su propiedad comiteable_type
        // para que en lugar de tener la ruta completa de la clase, solo tenga el nombre
        // de la clase sin namespace
        $comites = $comites->map(function ($comite) {
            // Creamos una instancia de ReflectionClass para obtener la información
            // de la clase de comiteable_type
            $reflectionClass = new \ReflectionClass($comite->comiteable_type);

            // Asignamos el nombre corto de la clase a la propiedad comiteable_type
            $comite->comiteable_type = $reflectionClass->getShortName();

            // Devolvemos el comité modificado
            return $comite;
        });

        return new GlobalResource($comites);
    }
}
