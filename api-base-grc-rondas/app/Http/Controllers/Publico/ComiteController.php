<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Http\Resources\GlobalResource;
use App\Http\Resources\Publico\CargoResource;
use App\Models\Publico\Cargo;
use App\Models\Publico\Comite;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComiteController extends Controller
{

    public function index()
    {
        $comites = Comite::all();
        return response()->json($comites);
    }

    public function show($id)
    {
        $comite = Comite::find($id);
        return response()->json($comite);
    }

    public function update(Request $request, $id)
    {
        $comite = Comite::find($id);
        $comite->update($request->all());
        return response()->json($comite);
    }

    public function destroy(string $id)
    {

        try {

            $comite = Comite::query()->findOrFail($id);
            $comite->eliminado = 1;
            $comite->deleted_by = auth()->user()->id;
            $comite->save();

            $response = [
                "state" => "success",
                "message" => "Registro eliminado",
            ];
            return response()->json($response,Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getComitesByRondero($id_rondero)
    {
        // Obtenemos todos los comités asociados a un rondero concreto
        // con sus relaciones de rondero, cargo y comiteable
        $comites = Comite::select('comite.id','comite.cargo_id','comite.comiteable_id','comite.comiteable_type', 'comite.fecha_inicio', 'comite.fecha_fin')
            ->with(['cargo','comiteable'])
            ->where('rondero_id', $id_rondero)
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

    public function store(Request $request)
    {

        try {

            DB::beginTransaction();


                $validated = $request->validate([
                    'rondero_id' => 'required|exists:rondero,id',
                    'cargo_id' => 'required|exists:cargo,id',
                    'comiteable_id' => 'required|integer',
                    'comiteable_type' => 'required|string',
                    'fecha_inicio' => 'required|date',
                    'fecha_fin' => 'required|date',
                ]);

                $allowedTypes = [
                    'App\\Models\\Publico\\Region',
                    'App\\Models\\Publico\\Provincia',
                    'App\\Models\\Publico\\Distrito',
                    'App\\Models\\Publico\\Sector',
                    'App\\Models\\Publico\\Base'
                ];



                if (!in_array($validated['comiteable_type'], $allowedTypes)) {
                    return response()->json(['error' => 'Tipo de entidad no válido.'], 422);
                }

                $comite = Comite::create($validated);
                DB::commit();
                return response()->json([
                    'state' => 'success',
                    'message' => 'Distrito registrada correctamente',
                ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json([],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function getComiteables(Request $request)
    {
        $type = $request->query('type');


        if (!in_array($type, ['App\Models\Publico\Region', 'App\Models\Publico\Provincia', 'App\Models\Publico\Distrito', 'App\Models\Publico\Sector', 'App\Models\Publico\Base'])) {
            return response()->json(['error' => 'Tipo de entidad no válido'], 422);
        }


        $comiteables = $type::where('eliminado', 0)->where('estado', 1)->get();  // Obtenemos todos los registros de esa entidad
//        $comiteables = $type::all()->where('eliminado', 0);  // Obtenemos todos los registros de esa entidad
//        Log::error($comiteables);
        return response()->json($comiteables);
    }

    public function getAvailableCargos(Request $request)
    {

        // Obtenemos los parámetros del request
        $comiteableType = $request->input('comiteable_type');
        $comiteableId = $request->input('comiteable_id');
        $startDate = $request->input('fecha_inicio');
//        $startDate = Carbon::createFromFormat('d/m/Y', $request->input('fecha_inicio'))->format('Y-m-d');
        $endDate = $request->input('fecha_fin');
//        $endDate = Carbon::createFromFormat('d/m/Y', $request->input('fecha_fin'))->format('Y-m-d');
        // Consulta para obtener los cargos no asignados a la entidad seleccionada en el rango de fechas
        $cargos = Cargo::whereDoesntHave('comites', function($query) use ($comiteableType, $comiteableId, $startDate, $endDate) {
            $query->where('comiteable_type', $comiteableType)
                ->where('comiteable_id', $comiteableId)
                ->where(function($dateQuery) use ($startDate, $endDate) {
                    $dateQuery->whereBetween('fecha_inicio', [$startDate, $endDate])
                        ->orWhereBetween('fecha_fin', [$startDate, $endDate])
                        ->orWhere(function($subQuery) use ($startDate, $endDate) {
                            $subQuery->where('fecha_inicio', '<=', $startDate)
                                ->where('fecha_fin', '>=', $endDate);
                        });
                });
        })->get();

//        Log::alert($cargos);
        return CargoResource::collection($cargos);
    }
}
