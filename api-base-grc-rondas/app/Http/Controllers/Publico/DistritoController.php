<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests\Admin\DistritoRequest; // CORRECTO: DistritoRequest
use App\Models\Publico\Distrito;
use App\Models\Publico\Sector;
use App\Models\Publico\Base;
use App\Http\Resources\Publico\DistritoResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Auth;

class DistritoController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->keyword;

        $distritos = Distrito::with('provincia')
            ->where(function ($query) use ($keyword) {
                if($keyword != '' && $keyword != null) {
                    $query->where('descripcion','ilike','%'.$keyword.'%')
                        ->orWhereHas('provincia', function ($q) use ($keyword) {
                            $q->where('descripcion', 'ilike', '%'.$keyword.'%');
                        });
                }
            })->where('eliminado', false);

        $distritos = $distritos->orderBy('id', 'desc');

        return DistritoResource::collection($distritos->paginate($request->limit));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DistritoRequest $request) // CAMBIADO: DistritoRequest en lugar de BaseRequest
    {
        try {
            DB::beginTransaction();

            // Verificamos si ya existe un distrito con la descripción nueva en la misma provincia
            $distrito_existe = Distrito::where('descripcion', $request->input('descripcion'))
                ->where('provincia_id', $request->input('provincia_id'))
                ->exists();

            if($distrito_existe) {
                return response()->json([
                    'state' => 'error',
                    'message' => 'Ya existe un distrito con la descripción ' . $request->input('descripcion') . ' en esta provincia'
                ], Response::HTTP_BAD_REQUEST);
            } else {
                // Nuevo Distrito
                $distrito = new Distrito();
                $distrito->descripcion = $request->input('descripcion');
                $distrito->provincia_id = $request->input('provincia_id');
                $distrito->estado = 1;
                $distrito->save();
            }

            DB::commit();
            return response()->json([
                'state' => 'success',
                'message' => 'Distrito registrado correctamente',
                'data' => new DistritoResource($distrito)
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json([
                'state' => 'error',
                'message' => 'Error al registrar el distrito'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $distrito = Distrito::with(['provincia', 'sectores'])->findOrFail($id);
        return new DistritoResource($distrito);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DistritoRequest $request, string $id)
    {
        try {
            DB::beginTransaction();

            // Actualizar DISTRITO
            $distrito = Distrito::query()->where('id','=',$id)->where('eliminado',false)->first();

            if($distrito) {
                // Verificar si ya existe otro distrito con la misma descripción en la misma provincia
                $existeDuplicado = Distrito::where('descripcion', $request->input('descripcion'))
                    ->where('provincia_id', $request->input('provincia_id'))
                    ->where('id', '!=', $id)
                    ->exists();

                if($existeDuplicado) {
                    return response()->json([
                        'state' => 'error',
                        'message' => 'Ya existe un distrito con la descripción ' . $request->input('descripcion') . ' en esta provincia'
                    ], Response::HTTP_BAD_REQUEST);
                }

                $distrito->descripcion = $request->input('descripcion');
                $distrito->provincia_id = $request->input('provincia_id');
                $distrito->save();

                DB::commit();

                // Cargar relación para el response
                $distrito->load('provincia');

                $response = [
                    "state" => "success",
                    "message" => "El registro se actualizó correctamente.",
                    "data" => new DistritoResource($distrito)
                ];
            } else {
                $response = [
                    "state" => "error",
                    "message" => "El registro no se puede actualizar o no existe.",
                ];
            }

            return response()->json($response, Response::HTTP_OK);

        } catch (Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json([
                'state' => 'error',
                'message' => 'Error al actualizar el distrito'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (!auth()->user()->hasPermissionTo('pub.distritos.eliminar', 'api')) {
            return response()->json([
                'state' => 'error',
                'message' => 'No tiene permiso para realizar esta acción'
            ], Response::HTTP_FORBIDDEN);
        }

        $usuario = Auth::user()->id;
        $distrito = Distrito::query()->find($id);

        if ($distrito) {
            DB::beginTransaction();
            try {
                // Verificar si hay sectores asociados a este distrito
                $sectoresCount = Sector::where('distrito_id', $id)
                    ->where('eliminado', false)
                    ->count();

                // Verificar si hay bases asociadas directamente a este distrito
                $basesCount = Base::where('distrito_id', $id)
                    ->where('eliminado', false)
                    ->count();

                if ($sectoresCount > 0 || $basesCount > 0) {
                    return response()->json([
                        'state' => 'error',
                        'message' => 'No se puede eliminar el distrito porque tiene sectores o bases asociadas'
                    ], Response::HTTP_BAD_REQUEST);
                }

                $distrito->eliminado = true;
                $distrito->deleted_by = $usuario;
                $distrito->save();

                DB::commit();

                return response()->json([
                    'state' => 'success',
                    'message' => 'Distrito eliminado correctamente'
                ], Response::HTTP_OK);
            } catch (\Exception $ex) {
                Log::alert($ex);
                DB::rollback();
                return response()->json([
                    'state' => 'error',
                    'message' => 'Error al eliminar el distrito: ' . $ex->getMessage()
                ], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return response()->json([
                'state' => 'error',
                'message' => 'Registro no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function activar($id)
    {
        if (!auth()->user()->hasPermissionTo('pub.distritos.activar', 'api')) {
            return response()->json([
                'state' => 'error',
                'message' => 'No tiene permiso para realizar esta acción'
            ], Response::HTTP_FORBIDDEN);
        }

        $distrito = Distrito::query()->where('id','=',$id)->where('estado',false)->first();

        if ($distrito) {
            DB::beginTransaction();
            try {
                $distrito->estado = true;
                $distrito->save();
                DB::commit();

                // Cargar relación para el response
                $distrito->load('provincia');

                return response()->json([
                    'state' => 'success',
                    'message' => 'Registro activado',
                    'data' => new DistritoResource($distrito)
                ], Response::HTTP_OK);
            } catch (\Exception $ex) {
                Log::alert($ex);
                DB::rollback();
                return response()->json([
                    'state' => 'error',
                    'message' => 'Error al activar el registro: ' . $ex->getMessage()
                ], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return response()->json([
                'state' => 'error',
                'message' => 'Registro no encontrado o ya se encuentra activado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function desactivar($id)
    {
        if (!auth()->user()->hasPermissionTo('pub.distritos.desactivar', 'api')) {
            return response()->json([
                'state' => 'error',
                'message' => 'No tiene permiso para realizar esta acción'
            ], Response::HTTP_FORBIDDEN);
        }

        $distrito = Distrito::query()->where('id','=',$id)->where('estado',true)->first();

        if ($distrito) {
            DB::beginTransaction();
            try {
                $distrito->estado = false;
                $distrito->save();
                DB::commit();

                // Cargar relación para el response
                $distrito->load('provincia');

                return response()->json([
                    'state' => 'success',
                    'message' => 'Registro desactivado',
                    'data' => new DistritoResource($distrito)
                ], Response::HTTP_OK);
            } catch (\Exception $ex) {
                Log::alert($ex);
                DB::rollback();
                return response()->json([
                    'state' => 'error',
                    'message' => 'Error al desactivar el registro: ' . $ex->getMessage()
                ], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return response()->json([
                'state' => 'error',
                'message' => 'Registro no encontrado o ya se encuentra desactivado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getSectores($id)
    {
        $sectores = Sector::with('distrito')
            ->where('distrito_id', $id)
            ->where('eliminado', false)
            ->where('estado', true)
            ->get();

        return response()->json($sectores, Response::HTTP_OK);
    }

    /**
     * Obtener bases por distrito (tanto directas como a través de sectores)
     */
    public function getBasesPorDistrito($id)
    {
        // Bases directamente en el distrito (sin sector)
        $basesDirectas = Base::with(['region', 'provincia', 'distrito'])
            ->where('distrito_id', $id)
            ->whereNull('sector_zona_id')
            ->where('eliminado', false)
            ->where('estado', true)
            ->get();

        // Bases a través de sectores del distrito
        $basesConSector = Base::with(['region', 'provincia', 'distrito', 'sector'])
            ->whereHas('sector', function ($query) use ($id) {
                $query->where('distrito_id', $id);
            })
            ->where('eliminado', false)
            ->where('estado', true)
            ->get();

        // Combinar ambas colecciones
        $todasLasBases = $basesDirectas->merge($basesConSector);

        return response()->json($todasLasBases, Response::HTTP_OK);
    }

    /**
     * Obtener distritos por provincia para selects
     */
    public function getDistritosPorProvincia($provinciaId)
    {
        $distritos = Distrito::where('provincia_id', $provinciaId)
            ->where('estado', true)
            ->where('eliminado', false)
            ->get(['id', 'descripcion']);

        return response()->json($distritos, Response::HTTP_OK);
    }
}
