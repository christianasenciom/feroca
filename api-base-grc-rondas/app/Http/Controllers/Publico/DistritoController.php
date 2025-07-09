<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests\Admin\DistritoRequest;
use App\Models\Publico\Distrito;
use App\Models\Publico\Sector;
use App\Http\Resources\Publico\DistritoResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Auth;

class DistritoController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->keyword;

        $distritos = Distrito::where(function ($query) use ($keyword) {
            if($keyword != '' && $keyword != null) {
                $query->where('descripcion','ilike','%'.$keyword.'%');
            }
        })->where('eliminado', false);

        $distritos = $distritos->orderBy('id', 'desc');

//        Log::alert($regiones->toRawSql());

        return DistritoResource::collection($distritos->paginate($request->limit));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DistritoRequest $request)
    {
        try {

            DB::beginTransaction();

            // Verificamos si ya existe una Distrito con la descripción nueva
            $distrito_existe = Distrito::where('descripcion', $request->input('descripcion'))->exists();
            if($distrito_existe) {
                return response()->json([
                    'state' => 'error',
                    'message' => 'Ya existe un registro con la descripción ' . $request->input('descripcion')
                ], Response::HTTP_BAD_REQUEST);
            }else{
                // nueva Distrito
                $distrito = new Distrito();
                $distrito->descripcion = $request->input('descripcion');
                $distrito->provincia_id = $request->input('provincia_id');
                $distrito->estado = 1;
                $distrito->save();
            }

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

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return new DistritoResource(Distrito::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DistritoRequest $request, string $id)
    {
        try {

            DB::beginTransaction();
            // Actualizar REGION
            $distrito = Distrito::query()->where('id','=',$id)->where('eliminado',false)->first();
            if($distrito)
            {
                $distrito->descripcion = $request->input('descripcion');
                $distrito->provincia_id = $request->input('provincia_id');
                $distrito->save();
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
        $distrito = Distrito::query()->find($id);
            if ($distrito) {
                DB::beginTransaction();
                try {
                    $distrito->eliminado = true;
                    $distrito->deleted_by =$usuario;
                    $distrito->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro eliminado", 'data' => $distrito], Response::HTTP_OK);
            } else {
                return response()->json(['error' => "Registro no encontrado"], Response::HTTP_NOT_FOUND);
            }
    }

    public function activar($id)
    {
        if (auth()->user()->hasPermissionTo('pub.distritos.activar', 'api')) {

            $distrito = Distrito::query()->where('id','=',$id)->where('estado',false)->first();

            if ($distrito) {
                DB::beginTransaction();
                try {
                    $distrito->estado = true;
                    $distrito->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro activado", 'data' => $distrito], Response::HTTP_OK);
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

        if (auth()->user()->hasPermissionTo('pub.distritos.desactivar', 'api')) {

            $distrito = Distrito::query()->where('id','=',$id)->where('estado',true)->first();

            if ($distrito) {
                DB::beginTransaction();
                try {
                    $distrito->estado = false;
                    $distrito->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro desactivado", 'data' => $distrito], Response::HTTP_OK);
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

    public function getSectores($id){

        $sectores = Sector::with('distrito')->where('distrito_id',$id)->get();
        return response()->json($sectores,Response::HTTP_OK);

    }
}
