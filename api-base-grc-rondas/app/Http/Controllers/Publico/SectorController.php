<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests\Admin\SectorRequest;
use App\Models\Publico\Sector;
use App\Models\Publico\Base;
use App\Http\Resources\Publico\SectorResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Auth;
use Exception;

class SectorController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->keyword;
        $regionId = $request->input('region_id');
        $provinciaId = $request->input('provincia_id');
        $distritoId = $request->input('distrito_id');

        $sectores = Sector::with('distrito.provincia.region')
            ->where(function ($query) use ($keyword) {
                if($keyword != '' && $keyword != null) {
                    $query->where('descripcion','ilike','%'.$keyword.'%')
                          ->orWhereHas('distrito', function ($q) use ($keyword) {
                              $q->where('descripcion', 'ilike', '%'.$keyword.'%');
                          });
                }
            })->where('eliminado', false);

        if (!empty($regionId)) {
            $sectores->whereHas('distrito.provincia', function ($q) use ($regionId) {
                $q->where('region_id', $regionId);
            });
        }

        if (!empty($provinciaId)) {
            $sectores->whereHas('distrito', function ($q) use ($provinciaId) {
                $q->where('provincia_id', $provinciaId);
            });
        }

        if (!empty($distritoId)) {
            $sectores->where('distrito_id', $distritoId);
        }

        $sectores = $sectores->orderBy('id', 'desc');

        return SectorResource::collection($sectores->paginate($request->limit));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SectorRequest $request)
    {
        try {
            DB::beginTransaction();

            // Verificamos si ya existe un sector con la descripción nueva
            $sector_existe = Sector::where('descripcion', $request->input('descripcion'))
                                   ->where('distrito_id', $request->input('distrito_id'))
                                   ->exists();

            if($sector_existe) {
                return response()->json([
                    'state' => 'error',
                    'message' => 'Ya existe un registro con la descripción ' . $request->input('descripcion') . ' en este distrito'
                ], Response::HTTP_BAD_REQUEST);
            } else {
                // nuevo Sector
                $sector = new Sector();
                $sector->descripcion = $request->input('descripcion');
                $sector->distrito_id = $request->input('distrito_id');
                $sector->estado = 1;
                $sector->save();
            }

            DB::commit();
            return response()->json([
                'state' => 'success',
                'message' => 'Sector registrado correctamente',
                'data' => new SectorResource($sector)
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json([
                'state' => 'error',
                'message' => 'Error al registrar el sector'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $sector = Sector::with('distrito')->findOrFail($id);
        return new SectorResource($sector);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SectorRequest $request, string $id)
    {
        try {
            DB::beginTransaction();

            // Actualizar SECTOR
            $sector = Sector::query()->where('id','=',$id)->where('eliminado',false)->first();

            if($sector) {
                // Verificar si ya existe otro sector con la misma descripción en el mismo distrito
                $existeDuplicado = Sector::where('descripcion', $request->input('descripcion'))
                                        ->where('distrito_id', $request->input('distrito_id'))
                                        ->where('id', '!=', $id)
                                        ->exists();

                if($existeDuplicado) {
                    return response()->json([
                        'state' => 'error',
                        'message' => 'Ya existe un sector con la descripción ' . $request->input('descripcion') . ' en este distrito'
                    ], Response::HTTP_BAD_REQUEST);
                }

                $sector->descripcion = $request->input('descripcion');
                $sector->distrito_id = $request->input('distrito_id');
                $sector->save();

                DB::commit();

                // Cargar relación para el response
                $sector->load('distrito');

                $response = [
                    "state" => "success",
                    "message" => "El registro se actualizó correctamente.",
                    "data" => new SectorResource($sector)
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
                'message' => 'Error al actualizar el sector'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (!auth()->check() || !auth()->user()->hasRole('SuperAdministrador')) {
            return response()->json([
                'state' => 'error',
                'message' => 'Solo el SuperAdministrador puede eliminar sectores'
            ], Response::HTTP_FORBIDDEN);
        }

        if (!auth()->user()->hasPermissionTo('pub.sectores.eliminar', 'api')) {
            return response()->json([
                'state' => 'error',
                'message' => 'No tiene permiso para realizar esta acción'
            ], Response::HTTP_FORBIDDEN);
        }

        $usuario = Auth::user()->id;
        $sector = Sector::query()->find($id);

        if ($sector) {
            DB::beginTransaction();
            try {
                // Verificar si hay bases asociadas a este sector
                $basesCount = Base::where('sector_zona_id', $id)
                                 ->where('eliminado', false)
                                 ->count();

                if ($basesCount > 0) {
                    return response()->json([
                        'state' => 'error',
                        'message' => 'No se puede eliminar el sector porque tiene bases asociadas'
                    ], Response::HTTP_BAD_REQUEST);
                }

                $sector->eliminado = true;
                $sector->deleted_by = $usuario;
                $sector->save();

                DB::commit();

                return response()->json([
                    'state' => 'success',
                    'message' => 'Sector eliminado correctamente'
                ], Response::HTTP_OK);
            } catch (\Exception $ex) {
                Log::alert($ex);
                DB::rollback();
                return response()->json([
                    'state' => 'error',
                    'message' => 'Error al eliminar el sector: ' . $ex->getMessage()
                ], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return response()->json([
                'state' => 'error',
                'message' => 'Registro no encontrado'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function eliminarMasivo(Request $request)
    {
        if (!auth()->check() || !auth()->user()->hasRole('SuperAdministrador')) {
            return response()->json([
                'state' => 'error',
                'message' => 'Solo el SuperAdministrador puede eliminar sectores'
            ], Response::HTTP_FORBIDDEN);
        }

        DB::beginTransaction();
        try {
            $keyword = $request->input('keyword');
            $regionId = $request->input('region_id');
            $provinciaId = $request->input('provincia_id');
            $distritoId = $request->input('distrito_id');
            $usuario = Auth::user()->id;

            $sectores = Sector::query()->where('eliminado', false);

            if (!empty($keyword)) {
                $sectores->where(function ($query) use ($keyword) {
                    $query->where('descripcion','ilike','%'.$keyword.'%')
                        ->orWhereHas('distrito', function ($q) use ($keyword) {
                            $q->where('descripcion', 'ilike', '%'.$keyword.'%');
                        });
                });
            }

            if (!empty($regionId)) {
                $sectores->whereHas('distrito.provincia', function ($q) use ($regionId) {
                    $q->where('region_id', $regionId);
                });
            }

            if (!empty($provinciaId)) {
                $sectores->whereHas('distrito', function ($q) use ($provinciaId) {
                    $q->where('provincia_id', $provinciaId);
                });
            }

            if (!empty($distritoId)) {
                $sectores->where('distrito_id', $distritoId);
            }

            $ids = $sectores->pluck('id');

            if ($ids->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'state' => 'error',
                    'message' => 'No hay sectores para eliminar con los filtros actuales'
                ], Response::HTTP_BAD_REQUEST);
            }

            $basesCount = Base::whereIn('sector_zona_id', $ids)
                ->where('eliminado', false)
                ->count();

            if ($basesCount > 0) {
                DB::rollBack();
                return response()->json([
                    'state' => 'error',
                    'message' => 'No se puede eliminar masivamente porque hay sectores con bases asociadas'
                ], Response::HTTP_BAD_REQUEST);
            }

            $eliminados = $ids->count();

            Sector::whereIn('id', $ids)->update([
                'eliminado' => true,
                'deleted_by' => $usuario,
            ]);

            DB::commit();

            return response()->json([
                'state' => 'success',
                'message' => 'Sectores eliminados correctamente',
                'deleted_count' => $eliminados,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en SectorController@eliminarMasivo', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'state' => 'error',
                'message' => 'Error al eliminar sectores de forma masiva'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function activar($id)
    {
        if (!auth()->user()->hasPermissionTo('pub.sectores.activar', 'api')) {
            return response()->json([
                'state' => 'error',
                'message' => 'No tiene permiso para realizar esta acción'
            ], Response::HTTP_FORBIDDEN);
        }

        $sector = Sector::query()->where('id','=',$id)->where('estado',false)->first();

        if ($sector) {
            DB::beginTransaction();
            try {
                $sector->estado = true;
                $sector->save();
                DB::commit();

                // Cargar relación para el response
                $sector->load('distrito');

                return response()->json([
                    'state' => 'success',
                    'message' => 'Registro activado',
                    'data' => new SectorResource($sector)
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
        if (!auth()->user()->hasPermissionTo('pub.sectores.desactivar', 'api')) {
            return response()->json([
                'state' => 'error',
                'message' => 'No tiene permiso para realizar esta acción'
            ], Response::HTTP_FORBIDDEN);
        }

        $sector = Sector::query()->where('id','=',$id)->where('estado',true)->first();

        if ($sector) {
            DB::beginTransaction();
            try {
                $sector->estado = false;
                $sector->save();
                DB::commit();

                // Cargar relación para el response
                $sector->load('distrito');

                return response()->json([
                    'state' => 'success',
                    'message' => 'Registro desactivado',
                    'data' => new SectorResource($sector)
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

    public function getBases($id)
    {
        $bases = Base::with(['region', 'provincia', 'distrito', 'sector'])
                    ->where('sector_zona_id', $id)
                    ->where('eliminado', false)
                    ->where('estado', true)
                    ->get();

        return response()->json($bases, Response::HTTP_OK);
    }

    /**
     * Obtener sectores por distrito para selects
     */
    public function getSectoresPorDistrito($distritoId)
    {
        $sectores = Sector::where('distrito_id', $distritoId)
                         ->where('estado', true)
                         ->where('eliminado', false)
                         ->get(['id', 'descripcion']);

        return response()->json($sectores, Response::HTTP_OK);
    }
}
