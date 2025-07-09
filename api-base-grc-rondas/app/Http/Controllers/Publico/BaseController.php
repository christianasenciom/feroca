<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\Auth\RonderoResource;
use App\Models\Publico\Rondero;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests\Admin\BaseRequest;
use App\Models\Publico\Base;
use App\Http\Resources\Publico\BaseResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Auth;

class BaseController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->keyword;

        $bases = Base::where(function ($query) use ($keyword) {
            if($keyword != '' && $keyword != null) {
                $query->where('descripcion','ilike','%'.$keyword.'%');
            }
        })->where('eliminado', false);

        $bases = $bases->orderBy('id', 'desc');

//        Log::alert($regiones->toRawSql());

        return BaseResource::collection($bases->paginate($request->limit));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BaseRequest $request)
    {
        try {

            DB::beginTransaction();

            // Verificamos si ya existe una base con la descripción nueva
            $base_existe = Base::where('descripcion', $request->input('descripcion'))->exists();
            if($base_existe) {
                return response()->json([
                    'state' => 'error',
                    'message' => 'Ya existe un registro con la descripción ' . $request->input('descripcion')
                ], Response::HTTP_BAD_REQUEST);
            }else{
                // nuevo Sector
                $base = new Base();
                $base->descripcion = $request->input('descripcion');
                $base->sector_zona_id = $request->input('sector_zona_id');
                $base->estado = 1;
                $base->save();
            }

            DB::commit();
            return response()->json([
                'state' => 'success',
                'message' => 'Sector registrado correctamente',
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json([],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return new BaseResource(Base::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BaseRequest $request, string $id)
    {
        try {

            DB::beginTransaction();
            // Actualizar Base
            $base = Base::query()->where('id','=',$id)->where('eliminado',false)->first();
            if($base)
            {
                $base->descripcion = $request->input('descripcion');
                $base->sector_zona_id = $request->input('sector_zona_id');
                $base->admin_id = $request->input('admin_id');
                $base->save();
                DB::commit();
                $response = [
                    "state" => "success",
                    "message" => "El registro se actualizo correctamente.",
                ];
            }
            else
            {
                $response = [
                    "state" => "error",
                    "message" => "El registro no se puede actualizar o no existe.",
                ];
            }

            return response()->json($response,Response::HTTP_OK);

        } catch (Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json([],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $usuario = Auth::user()->id;
        $base = Base::query()->find($id);
            if ($base) {
                DB::beginTransaction();
                try {
                    $base->eliminado = true;
                    $base->deleted_by =$usuario;
                    $base->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro eliminado", 'data' => $base], Response::HTTP_OK);
            } else {
                return response()->json(['error' => "Registro no encontrado"], Response::HTTP_NOT_FOUND);
            }
    }

    public function activar($id)
    {
        if (auth()->user()->hasPermissionTo('pub.bases.activar', 'api')) {

            $base = Base::query()->where('id','=',$id)->where('estado',false)->first();

            if ($base) {
                DB::beginTransaction();
                try {
                    $base->estado = true;
                    $base->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro activado", 'data' => $base], Response::HTTP_OK);
            } else {
                return response()->json(['error' => "Registro no encontrado o ya se enuentra activado"], Response::HTTP_NOT_FOUND);
            }
        } else {
            $msg = [
                'title' => 'Acceso denegado',
                'message' => 'Usted no tiene permiso para realizar esta acción',
            ];
            return response()->json(['error' => $msg], Response::HTTP_BAD_REQUEST);
        }
    }

    public function desactivar($id)
    {

        if (auth()->user()->hasPermissionTo('pub.bases.desactivar', 'api')) {

            $base = Base::query()->where('id','=',$id)->where('estado',true)->first();

            if ($base) {
                DB::beginTransaction();
                try {
                    $base->estado = false;
                    $base->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro desactivado", 'data' => $base], Response::HTTP_OK);
            } else {
                return response()->json(['error' => "Registro no encontrado o ya se encuentra desactivado"], Response::HTTP_NOT_FOUND);
            }
        } else {
            $msg = [
                'title' => 'Acceso denegado',
                'message' => 'Usted no tiene permiso para realizar esta acción',
            ];
            return response()->json(['error' => $msg], Response::HTTP_BAD_REQUEST);
        }
    }
    public function getRonderosByBase(Request $request)
    {

        $keyword = $request->keyword;
        $ronderos = Rondero::query()
            ->with('persona')
            ->where('eliminado','=', false)
            ->where('estado', true)
            ->where('base_id', $request->base_id);
        $ids_excluir = $request->ids_excluir; // Devuelve el array completo

        if ($ids_excluir != ''){
            $ronderos = $ronderos->whereNotIn('id', $ids_excluir);
        }
        if($keyword != '' && $keyword != null) {
            $ronderos =
                $ronderos->whereHas('persona',function($query) use ($keyword) {
                    $query->where('docIdentidad', 'ilike', '%' . $keyword . '%')
                        ->orWhereRaw("CONCAT(nombres, ' ', ' ', apellido_paterno, ' ', apellido_materno) ilike ?", ['%' . $keyword . '%'])
                        ->orWhereRaw("CONCAT(apellido_paterno, ' ', apellido_materno, ' ', nombres, ' ') ilike ?", ['%' . $keyword . '%'])
                        ->orWhere('apellido_paterno', 'ilike', '%' . $keyword . '%')
                        ->orWhere('apellido_materno', 'ilike', '%' . $keyword . '%')
                        ->orWhere('nombres', 'ilike', '%' . $keyword . '%');
                });
        }

        $ronderos = $ronderos->orderBy('id', 'desc');

//        Log::alert($ronderos->toRawSql());

        return RonderoResource::collection($ronderos->paginate($request->limit));
    }
}
