<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests\Admin\ProvinciaRequest;
use App\Models\Publico\Provincia;
use App\Models\Publico\Distrito;
use App\Http\Resources\Publico\ProvinciaResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Auth;

class ProvinciaController extends Controller
{
     /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $keyword = $request->keyword;

        $provincias = Provincia::where(function ($query) use ($keyword) {
            if($keyword != '' && $keyword != null) {
                $query->where('descripcion','ilike','%'.$keyword.'%');
            }
        })->where('eliminado', false);

        $provincias = $provincias->orderBy('id', 'desc');

//        Log::alert($regiones->toRawSql());

        return ProvinciaResource::collection($provincias->paginate($request->limit));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProvinciaRequest $request)
    {
        try {

            DB::beginTransaction();

            // Verificamos si ya existe una provincia con la descripción nueva
            $provincia_existe = Provincia::where('descripcion', $request->input('descripcion'))->exists();
            if($provincia_existe) {
                return response()->json([
                    'state' => 'error',
                    'message' => 'Ya existe un registro con la descripción ' . $request->input('descripcion')
                ], Response::HTTP_BAD_REQUEST);
            }else{
                // nueva provincia
                $provincia = new Provincia();
                $provincia->descripcion = $request->input('descripcion');
                $provincia->region_id = $request->input('region_id');
                $provincia->estado = 1;
                $provincia->save();
            }

            DB::commit();
            return response()->json([
                'state' => 'success',
                'message' => 'Provincia registrada correctamente',
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
        return new ProvinciaResource(Provincia::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProvinciaRequest $request, string $id)
    {
        try {

            DB::beginTransaction();
            // Actualizar REGION
            $provincia = Provincia::query()->where('id','=',$id)->where('eliminado',false)->first();
            if($provincia)
            {
                $provincia->descripcion = $request->input('descripcion');
                $provincia->region_id = $request->input('region_id');
                $provincia->save();
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
        $provincia = Provincia::query()->find($id);
            if ($provincia) {
                DB::beginTransaction();
                try {
                    $provincia->eliminado = true;
                    $provincia->deleted_by =$usuario;
                    $provincia->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro eliminado", 'data' => $provincia], Response::HTTP_OK);
            } else {
                return response()->json(['error' => "Registro no encontrado"], Response::HTTP_NOT_FOUND);
            }
    }

    public function activar($id)
    {
        if (auth()->user()->hasPermissionTo('pub.provincias.activar', 'api')) {

            $provincia = Provincia::query()->where('id','=',$id)->where('estado',false)->first();

            if ($provincia) {
                DB::beginTransaction();
                try {
                    $provincia->estado = true;
                    $provincia->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro activado", 'data' => $provincia], Response::HTTP_OK);
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

        if (auth()->user()->hasPermissionTo('pub.provincias.desactivar', 'api')) {

            $provincia = Provincia::query()->where('id','=',$id)->where('estado',true)->first();

            if ($provincia) {
                DB::beginTransaction();
                try {
                    $provincia->estado = false;
                    $provincia->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro desactivado", 'data' => $provincia], Response::HTTP_OK);
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

    public function getDistritos($id){

        $distritos = Distrito::with('provincia')->where('provincia_id',$id)->get();
        return response()->json($distritos,Response::HTTP_OK);

    }
}
