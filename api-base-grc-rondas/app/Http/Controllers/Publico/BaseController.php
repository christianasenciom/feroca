<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\Auth\RonderoResource;
use App\Models\Publico\Rondero;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests\Admin\BaseRequest;
use App\Models\Publico\Base;
use App\Models\Publico\Region;
use App\Models\Publico\Provincia;
use App\Models\Publico\Distrito;
use App\Models\Publico\Sector;
use App\Models\Publico\Persona;
use App\Http\Resources\Publico\BaseResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Auth;
use Exception;

class BaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $keyword = $request->keyword;
            $regionId = $request->input('region_id');
            $provinciaId = $request->input('provincia_id');
            $distritoId = $request->input('distrito_id');
            $sectorZonaId = $request->input('sector_zona_id');

            $bases = Base::with(['region', 'provincia', 'distrito', 'sector', 'admin'])
                ->where('eliminado', false);

            // Aplicar filtro de búsqueda si existe keyword
            if (!empty($keyword)) {
                $bases = $bases->where(function ($query) use ($keyword) {
                    $query->where('nombre_descripcion', 'ilike', '%' . $keyword . '%')
                        ->orWhere('numero_partida_registral', 'ilike', '%' . $keyword . '%')
                        ->orWhereHas('region', function ($q) use ($keyword) {
                            $q->where('descripcion', 'ilike', '%' . $keyword . '%');
                        })
                        ->orWhereHas('provincia', function ($q) use ($keyword) {
                            $q->where('descripcion', 'ilike', '%' . $keyword . '%');
                        })
                        ->orWhereHas('distrito', function ($q) use ($keyword) {
                            $q->where('descripcion', 'ilike', '%' . $keyword . '%');
                        })
                        ->orWhereHas('sector', function ($q) use ($keyword) {
                            $q->where('descripcion', 'ilike', '%' . $keyword . '%');
                        });
                });
            }

            if (!empty($regionId)) {
                $bases->where('region_id', $regionId);
            }

            if (!empty($provinciaId)) {
                $bases->where('provincia_id', $provinciaId);
            }

            if (!empty($distritoId)) {
                $bases->where('distrito_id', $distritoId);
            }

            if ($request->filled('sector_zona_id')) {
                if ((string)$sectorZonaId === 'sin-sector') {
                    $bases->whereNull('sector_zona_id');
                } else {
                    $bases->where('sector_zona_id', $sectorZonaId);
                }
            }

            $bases = $bases->orderBy('id', 'desc');

            return BaseResource::collection($bases->paginate($request->limit ?? 10));

        } catch (Exception $e) {
            Log::error('Error en BaseController@index:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'state' => 'error',
                'message' => 'Error al obtener la lista de bases'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BaseRequest $request)
    {
        DB::beginTransaction();
        try {
            Log::info('BaseController@store - Datos recibidos:', $request->all());

            $validatedData = $request->validated();

            // Verificar que el sector pertenezca al distrito si se proporciona
            if ($request->filled('sector_zona_id')) {
                $sector = Sector::find($request->sector_zona_id);
                if ($sector && $sector->distrito_id != $request->distrito_id) {
                    return response()->json([
                        'state' => 'error',
                        'message' => 'El sector seleccionado no pertenece al distrito'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Verificar que el administrador exista si se proporciona
            if ($request->filled('admin_id')) {
                $persona = Persona::find($request->admin_id);
                if (!$persona) {
                    return response()->json([
                        'state' => 'error',
                        'message' => 'La persona seleccionada como administrador no existe'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Preparar datos para creación
            $baseData = [
                'nombre_descripcion' => $request->nombre_descripcion,
                'numero_partida_registral' => $request->filled('numero_partida_registral') ? $request->numero_partida_registral : null,
                'region_id' => $request->region_id,
                'provincia_id' => $request->provincia_id,
                'distrito_id' => $request->distrito_id,
                'estado' => true,
                'eliminado' => false,
            ];

            // Campos opcionales
            if ($request->filled('sector_zona_id')) {
                $baseData['sector_zona_id'] = $request->sector_zona_id;
            }

            if ($request->filled('admin_id')) {
                $baseData['admin_id'] = $request->admin_id;
            }

            // Crear la base
            $base = Base::create($baseData);

            DB::commit();

            Log::info('BaseController@store - Base creada exitosamente:', ['id' => $base->id]);

            // Cargar relaciones para la respuesta
            $base->load(['region', 'provincia', 'distrito', 'sector', 'admin']);

            return response()->json([
                'state' => 'success',
                'message' => 'Base registrada correctamente',
                'data' => new BaseResource($base)
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            DB::rollback();

            Log::error('Error en BaseController@store:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'data' => $request->all()
            ]);

            return response()->json([
                'state' => 'error',
                'message' => 'Error al registrar la base: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $base = Base::with(['region', 'provincia', 'distrito', 'sector', 'admin'])
                ->where('eliminado', false)
                ->findOrFail($id);

            return new BaseResource($base);

        } catch (Exception $e) {
            Log::error('Error en BaseController@show:', [
                'id' => $id,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'state' => 'error',
                'message' => 'Base no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BaseRequest $request, string $id)
    {
        DB::beginTransaction();
        try {
            Log::info('BaseController@update - Datos recibidos:', ['id' => $id, 'data' => $request->all()]);

            // Buscar base existente
            $base = Base::where('id', $id)->where('eliminado', false)->first();

            if (!$base) {
                return response()->json([
                    'state' => 'error',
                    'message' => 'La base no existe o ha sido eliminada'
                ], Response::HTTP_NOT_FOUND);
            }

            $validatedData = $request->validated();

            // Verificar que el sector pertenezca al distrito si se proporciona
            if ($request->filled('sector_zona_id')) {
                $sector = Sector::find($request->sector_zona_id);
                if ($sector && $sector->distrito_id != $request->distrito_id) {
                    return response()->json([
                        'state' => 'error',
                        'message' => 'El sector seleccionado no pertenece al distrito'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Verificar que el administrador exista si se proporciona
            if ($request->filled('admin_id')) {
                $persona = Persona::find($request->admin_id);
                if (!$persona) {
                    return response()->json([
                        'state' => 'error',
                        'message' => 'La persona seleccionada como administrador no existe'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Actualizar campos
            $base->nombre_descripcion = $request->nombre_descripcion;
            $base->numero_partida_registral = $request->filled('numero_partida_registral') ? $request->numero_partida_registral : null;
            $base->region_id = $request->region_id;
            $base->provincia_id = $request->provincia_id;
            $base->distrito_id = $request->distrito_id;
            $base->sector_zona_id = $request->filled('sector_zona_id') ? $request->sector_zona_id : null;
            $base->admin_id = $request->filled('admin_id') ? $request->admin_id : null;

            $base->save();

            DB::commit();

            Log::info('BaseController@update - Base actualizada exitosamente:', ['id' => $base->id]);

            // Cargar relaciones para la respuesta
            $base->load(['region', 'provincia', 'distrito', 'sector', 'admin']);

            return response()->json([
                'state' => 'success',
                'message' => 'Base actualizada correctamente',
                'data' => new BaseResource($base)
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            DB::rollback();

            Log::error('Error en BaseController@update:', [
                'id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'state' => 'error',
                'message' => 'Error al actualizar la base: ' . $e->getMessage()
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
                'message' => 'Solo el SuperAdministrador puede eliminar bases'
            ], Response::HTTP_FORBIDDEN);
        }

        if (!auth()->user()->hasPermissionTo('pub.bases.eliminar', 'api')) {
            return response()->json([
                'state' => 'error',
                'message' => 'No tiene permiso para realizar esta acción'
            ], Response::HTTP_FORBIDDEN);
        }

        $usuario = Auth::user()->id;
        $base = Base::where('eliminado', false)->find($id);

        if ($base) {
            DB::beginTransaction();
            try {
                $base->eliminado = true;
                $base->deleted_by = $usuario;
                $base->save();

                DB::commit();

                return response()->json([
                    'state' => 'success',
                    'message' => 'Base eliminada correctamente'
                ], Response::HTTP_OK);

            } catch (\Exception $ex) {
                DB::rollback();
                Log::error('Error en BaseController@destroy:', [
                    'id' => $id,
                    'error' => $ex->getMessage()
                ]);

                return response()->json([
                    'state' => 'error',
                    'message' => 'Error al eliminar la base: ' . $ex->getMessage()
                ], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return response()->json([
                'state' => 'error',
                'message' => 'Base no encontrada'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function eliminarMasivo(Request $request)
    {
        if (!auth()->check() || !auth()->user()->hasRole('SuperAdministrador')) {
            return response()->json([
                'state' => 'error',
                'message' => 'Solo el SuperAdministrador puede eliminar bases'
            ], Response::HTTP_FORBIDDEN);
        }

        DB::beginTransaction();
        try {
            $keyword = $request->input('keyword');
            $regionId = $request->input('region_id');
            $provinciaId = $request->input('provincia_id');
            $distritoId = $request->input('distrito_id');
            $sectorZonaId = $request->input('sector_zona_id');
            $usuario = Auth::user()->id;

            $bases = Base::query()->where('eliminado', false);

            if (!empty($keyword)) {
                $bases->where(function ($query) use ($keyword) {
                    $query->where('nombre_descripcion', 'ilike', '%' . $keyword . '%')
                        ->orWhere('numero_partida_registral', 'ilike', '%' . $keyword . '%')
                        ->orWhereHas('region', function ($q) use ($keyword) {
                            $q->where('descripcion', 'ilike', '%' . $keyword . '%');
                        })
                        ->orWhereHas('provincia', function ($q) use ($keyword) {
                            $q->where('descripcion', 'ilike', '%' . $keyword . '%');
                        })
                        ->orWhereHas('distrito', function ($q) use ($keyword) {
                            $q->where('descripcion', 'ilike', '%' . $keyword . '%');
                        })
                        ->orWhereHas('sector', function ($q) use ($keyword) {
                            $q->where('descripcion', 'ilike', '%' . $keyword . '%');
                        });
                });
            }

            if (!empty($regionId)) {
                $bases->where('region_id', $regionId);
            }

            if (!empty($provinciaId)) {
                $bases->where('provincia_id', $provinciaId);
            }

            if (!empty($distritoId)) {
                $bases->where('distrito_id', $distritoId);
            }

            if ($request->filled('sector_zona_id')) {
                if ((string)$sectorZonaId === 'sin-sector') {
                    $bases->whereNull('sector_zona_id');
                } else {
                    $bases->where('sector_zona_id', $sectorZonaId);
                }
            }

            $ids = $bases->pluck('id');
            $eliminados = $ids->count();

            if ($eliminados === 0) {
                DB::rollBack();
                return response()->json([
                    'state' => 'error',
                    'message' => 'No hay bases para eliminar con los filtros actuales'
                ], Response::HTTP_BAD_REQUEST);
            }

            Base::whereIn('id', $ids)->update([
                'eliminado' => true,
                'deleted_by' => $usuario,
            ]);

            DB::commit();

            return response()->json([
                'state' => 'success',
                'message' => 'Bases eliminadas correctamente',
                'deleted_count' => $eliminados,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en BaseController@eliminarMasivo', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'state' => 'error',
                'message' => 'Error al eliminar bases de forma masiva'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function activar($id)
    {
        if (!auth()->user()->hasPermissionTo('pub.bases.activar', 'api')) {
            return response()->json([
                'state' => 'error',
                'message' => 'No tiene permiso para realizar esta acción'
            ], Response::HTTP_FORBIDDEN);
        }

        DB::beginTransaction();
        try {
            $base = Base::where('id', $id)->where('eliminado', false)->where('estado', false)->first();

            if (!$base) {
                return response()->json([
                    'state' => 'error',
                    'message' => 'Base no encontrada o ya está activada'
                ], Response::HTTP_NOT_FOUND);
            }

            $base->estado = true;
            $base->save();

            DB::commit();

            $base->load(['region', 'provincia', 'distrito', 'sector', 'admin']);

            return response()->json([
                'state' => 'success',
                'message' => 'Base activada correctamente',
                'data' => new BaseResource($base)
            ], Response::HTTP_OK);

        } catch (\Exception $ex) {
            DB::rollback();
            Log::error('Error en BaseController@activar:', [
                'id' => $id,
                'error' => $ex->getMessage()
            ]);

            return response()->json([
                'state' => 'error',
                'message' => 'Error al activar la base: ' . $ex->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function desactivar($id)
    {
        if (!auth()->user()->hasPermissionTo('pub.bases.desactivar', 'api')) {
            return response()->json([
                'state' => 'error',
                'message' => 'No tiene permiso para realizar esta acción'
            ], Response::HTTP_FORBIDDEN);
        }

        DB::beginTransaction();
        try {
            $base = Base::where('id', $id)->where('eliminado', false)->where('estado', true)->first();

            if (!$base) {
                return response()->json([
                    'state' => 'error',
                    'message' => 'Base no encontrada o ya está desactivada'
                ], Response::HTTP_NOT_FOUND);
            }

            $base->estado = false;
            $base->save();

            DB::commit();

            $base->load(['region', 'provincia', 'distrito', 'sector', 'admin']);

            return response()->json([
                'state' => 'success',
                'message' => 'Base desactivada correctamente',
                'data' => new BaseResource($base)
            ], Response::HTTP_OK);

        } catch (\Exception $ex) {
            DB::rollback();
            Log::error('Error en BaseController@desactivar:', [
                'id' => $id,
                'error' => $ex->getMessage()
            ]);

            return response()->json([
                'state' => 'error',
                'message' => 'Error al desactivar la base: ' . $ex->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function getRonderosByBase(Request $request)
    {
        try {
            $keyword = $request->keyword;
            $ronderos = Rondero::query()
                ->with('persona')
                ->where('eliminado', false)
                ->where('estado', true)
                ->where('base_id', $request->base_id);

            $ids_excluir = $request->ids_excluir;

            if ($ids_excluir != '') {
                $ronderos = $ronderos->whereNotIn('id', $ids_excluir);
            }

            if (!empty($keyword)) {
                $ronderos = $ronderos->whereHas('persona', function($query) use ($keyword) {
                    $query->where('docIdentidad', 'ilike', '%' . $keyword . '%')
                        ->orWhereRaw("CONCAT(nombres, ' ', apellido_paterno, ' ', apellido_materno) ilike ?", ['%' . $keyword . '%'])
                        ->orWhereRaw("CONCAT(apellido_paterno, ' ', apellido_materno, ' ', nombres) ilike ?", ['%' . $keyword . '%'])
                        ->orWhere('apellido_paterno', 'ilike', '%' . $keyword . '%')
                        ->orWhere('apellido_materno', 'ilike', '%' . $keyword . '%')
                        ->orWhere('nombres', 'ilike', '%' . $keyword . '%');
                });
            }

            $ronderos = $ronderos->orderBy('id', 'desc');

            return RonderoResource::collection($ronderos->paginate($request->limit ?? 10));
        } catch (Exception $e) {
            Log::error('Error en BaseController@getRonderosByBase:', [
                'message' => $e->getMessage(),
                'base_id' => $request->base_id,
                'keyword' => $request->keyword
            ]);

            return response()->json([
                'state' => 'error',
                'message' => 'Error al obtener ronderos de la base'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Filtrar provincias por región
     */
    public function filtrarProvinciasPorRegion($regionId)
    {
        try {
            $provincias = Provincia::where('region_id', $regionId)
                ->where('estado', true)
                ->where('eliminado', false)
                ->get(['id', 'descripcion']);

            return response()->json($provincias);
        } catch (Exception $e) {
            Log::error('Error en BaseController@filtrarProvinciasPorRegion:', [
                'region_id' => $regionId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'state' => 'error',
                'message' => 'Error al filtrar provincias'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Filtrar distritos por provincia
     */
    public function filtrarDistritosPorProvincia($provinciaId)
    {
        try {
            $distritos = Distrito::where('provincia_id', $provinciaId)
                ->where('estado', true)
                ->where('eliminado', false)
                ->get(['id', 'descripcion']);

            return response()->json($distritos);
        } catch (Exception $e) {
            Log::error('Error en BaseController@filtrarDistritosPorProvincia:', [
                'provincia_id' => $provinciaId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'state' => 'error',
                'message' => 'Error al filtrar distritos'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Filtrar sectores por distrito
     */
    public function filtrarSectoresPorDistrito($distritoId)
    {
        try {
            $sectores = Sector::where('distrito_id', $distritoId)
                ->where('estado', true)
                ->where('eliminado', false)
                ->get(['id', 'descripcion']);

            return response()->json($sectores);
        } catch (Exception $e) {
            Log::error('Error en BaseController@filtrarSectoresPorDistrito:', [
                'distrito_id' => $distritoId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'state' => 'error',
                'message' => 'Error al filtrar sectores'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtener bases por distrito (con o sin sector)
     */
    public function getBasesPorDistrito($distritoId)
    {
        try {
            $bases = Base::with(['region', 'provincia', 'distrito', 'sector', 'admin'])
                ->where('distrito_id', $distritoId)
                ->where('eliminado', false)
                ->where('estado', true)
                ->get();

            return BaseResource::collection($bases);
        } catch (Exception $e) {
            Log::error('Error en BaseController@getBasesPorDistrito:', [
                'distrito_id' => $distritoId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'state' => 'error',
                'message' => 'Error al obtener bases por distrito'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtener bases por sector.
     * Incluye también bases sin sector del mismo distrito para cubrir casos
     * donde algunas bases fueron registradas directamente sin sector.
     */
    public function getBases($id)
    {
        try {
            $sector = Sector::where('id', $id)
                ->where('estado', true)
                ->where('eliminado', false)
                ->first();

            if (!$sector) {
                return response()->json([
                    'state' => 'error',
                    'message' => 'Sector no encontrado o inactivo'
                ], Response::HTTP_NOT_FOUND);
            }

            $bases = Base::with(['region', 'provincia', 'distrito', 'sector', 'admin'])
                ->where('eliminado', false)
                ->where('estado', true)
                ->where(function ($query) use ($id, $sector) {
                    $query->where('sector_zona_id', $id)
                        ->orWhere(function ($subQuery) use ($sector) {
                            $subQuery->whereNull('sector_zona_id')
                                ->where('distrito_id', $sector->distrito_id);
                        });
                })
                ->orderBy('nombre_descripcion')
                ->get();

            return BaseResource::collection($bases);
        } catch (Exception $e) {
            Log::error('Error en BaseController@getBases:', [
                'sector_id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'state' => 'error',
                'message' => 'Error al obtener bases por sector'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
