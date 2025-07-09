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

class SectorController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->keyword;

        $sectores = Sector::where(function ($query) use ($keyword) {
            if($keyword != '' && $keyword != null) {
                $query->where('descripcion','ilike','%'.$keyword.'%');
            }
        })->where('eliminado', false);

        $sectores = $sectores->orderBy('id', 'desc');

//        Log::alert($regiones->toRawSql());

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
            $sector_existe = Sector::where('descripcion', $request->input('descripcion'))->exists();
            if($sector_existe) {
                return response()->json([
                    'state' => 'error',
                    'message' => 'Ya existe un registro con la descripción ' . $request->input('descripcion')
                ], Response::HTTP_BAD_REQUEST);
            }else{
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
        return new SectorResource(Sector::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SectorRequest $request, string $id)
    {
        try {

            DB::beginTransaction();
            // Actualizar REGION
            $sector = Sector::query()->where('id','=',$id)->where('eliminado',false)->first();
            if($sector)
            {
                $sector->descripcion = $request->input('descripcion');
                $sector->distrito_id = $request->input('distrito_id');
                $sector->save();
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
        $sector = Sector::query()->find($id);
            if ($sector) {
                DB::beginTransaction();
                try {
                    $sector->eliminado = true;
                    $sector->deleted_by =$usuario;
                    $sector->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro eliminado", 'data' => $sector], Response::HTTP_OK);
            } else {
                return response()->json(['error' => "Registro no encontrado"], Response::HTTP_NOT_FOUND);
            }
    }

    public function activar($id)
    {
        if (auth()->user()->hasPermissionTo('pub.sectores.activar', 'api')) {

            $sector = Sector::query()->where('id','=',$id)->where('estado',false)->first();

            if ($sector) {
                DB::beginTransaction();
                try {
                    $sector->estado = true;
                    $sector->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro activado", 'data' => $sector], Response::HTTP_OK);
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

        if (auth()->user()->hasPermissionTo('pub.sectores.desactivar', 'api')) {

            $sector = Sector::query()->where('id','=',$id)->where('estado',true)->first();

            if ($sector) {
                DB::beginTransaction();
                try {
                    $sector->estado = false;
                    $sector->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro desactivado", 'data' => $sector], Response::HTTP_OK);
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

    public function getBases($id){

        $bases = Base::with('sector')->where('sector_zona_id',$id)->get();
        return response()->json($bases,Response::HTTP_OK);

    }
}
