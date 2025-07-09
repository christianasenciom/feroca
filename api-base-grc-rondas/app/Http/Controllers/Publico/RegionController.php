<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests\Admin\RegionRequest;
use App\Models\Publico\Region;
use App\Models\Publico\Provincia;
use App\Http\Resources\Publico\RegionResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Auth;


class RegionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $keyword = $request->keyword;

        $regiones = Region::where(function ($query) use ($keyword) {
            if($keyword != '' && $keyword != null) {
                $query->where('descripcion','ilike','%'.$keyword.'%');
            }
        })->where('eliminado', false);

        $regiones = $regiones->orderBy('id', 'desc');

//        Log::alert($regiones->toRawSql());

        return RegionResource::collection($regiones->paginate($request->limit));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RegionRequest $request)
    {
        try {

            DB::beginTransaction();

            // Verificamos si ya existe una region con la descripción nueva
            $region_existe = Region::where('descripcion', $request->input('descripcion'))->exists();
            if($region_existe) {
                return response()->json([
                    'state' => 'error',
                    'message' => 'Ya existe un registro con la descripción ' . $request->input('descripcion')
                ], Response::HTTP_BAD_REQUEST);
            }else{
                // nueva región
                $region = new Region();
                $region->descripcion = $request->input('descripcion');
                $region->estado = 1;
                $region->save();
            }

            DB::commit();
            return response()->json([
                'state' => 'success',
                'message' => 'Región registrada correctamente',
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
        return new RegionResource(Region::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RegionRequest $request, string $id)
    {
        try {

            DB::beginTransaction();
            // Actualizar REGION
            $region = Region::query()->where('id','=',$id)->where('eliminado',false)->first();
            if($region)
            {
                $region->descripcion = $request->input('descripcion');
                $region->save();
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
        $region = Region::query()->find($id);
            if ($region) {
                DB::beginTransaction();
                try {
                    $region->eliminado = true;
                    $region->deleted_by =$usuario;
                    $region->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro eliminado", 'data' => $region], Response::HTTP_OK);
            } else {
                return response()->json(['error' => "Registro no encontrado"], Response::HTTP_NOT_FOUND);
            }
    }

    public function activar($id)
    {
        if (auth()->user()->hasPermissionTo('pub.regiones.activar', 'api')) {

            $region = Region::query()->where('id','=',$id)->where('estado',false)->first();

            if ($region) {
                DB::beginTransaction();
                try {
                    $region->estado = true;
                    $region->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro activado", 'data' => $region], Response::HTTP_OK);
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

        if (auth()->user()->hasPermissionTo('pub.regiones.desactivar', 'api')) {

            $region = Region::query()->where('id','=',$id)->where('estado',true)->first();

            if ($region) {
                DB::beginTransaction();
                try {
                    $region->estado = false;
                    $region->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro desactivado", 'data' => $region], Response::HTTP_OK);
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

    public function provincias($id){

        $provincias = Provincia::with('region')->where('region_id',$id)->get();
        return response()->json($provincias,Response::HTTP_OK);

    }
}
