<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests\Admin\CargoRequest;
use App\Models\Publico\Cargo;
use App\Http\Resources\Publico\CargoResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Auth;

class CargoController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->keyword;

        $cargos = Cargo::where(function ($query) use ($keyword) {
            if($keyword != '' && $keyword != null) {
                $query->where('descripcion','ilike','%'.$keyword.'%');
            }
        })->where('eliminado', false);

        $cargos = $cargos->orderBy('id', 'desc');

        return CargoResource::collection($cargos->paginate($request->limit));
    }

    public function store(CargoRequest $request)
    {
        try {

            DB::beginTransaction();

            $cargo_existe = Cargo::where('descripcion', $request->input('descripcion'))->exists();
            if($cargo_existe) {
                return response()->json([
                    'state' => 'error',
                    'message' => 'Ya existe un registro con la descripción ' . $request->input('descripcion')
                ], Response::HTTP_BAD_REQUEST);
            }else{
                $cargo = new Cargo();
                $cargo->descripcion = $request->input('descripcion');
                $cargo->estado = 1;
                $cargo->save();
            }

            DB::commit();
            return response()->json([
                'state' => 'success',
                'message' => 'Cargo registrado correctamente',
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
        return new CargoResource(Cargo::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CargoRequest $request, string $id)
    {
        try {

            DB::beginTransaction();
            $cargo = Cargo::query()->where('id','=',$id)->where('eliminado',false)->first();
            if($cargo)
            {
                $cargo->descripcion = $request->input('descripcion');
                $cargo->save();
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
        $cargo = Cargo::query()->find($id);
            if ($cargo) {
                DB::beginTransaction();
                try {
                    $cargo->eliminado = true;
                    $cargo->deleted_by =$usuario;
                    $cargo->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro eliminado", 'data' => $cargo], Response::HTTP_OK);
            } else {
                return response()->json(['error' => "Registro no encontrado"], Response::HTTP_NOT_FOUND);
            }
    }

    public function activar($id)
    {
        if (auth()->user()->hasPermissionTo('pub.cargos.activar', 'api')) {

            $cargo = Cargo::query()->where('id','=',$id)->where('estado',false)->first();

            if ($cargo) {
                DB::beginTransaction();
                try {
                    $cargo->estado = true;
                    $cargo->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro activado", 'data' => $cargo], Response::HTTP_OK);
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

        if (auth()->user()->hasPermissionTo('pub.cargos.desactivar', 'api')) {

            $cargo = Cargo::query()->where('id','=',$id)->where('estado',true)->first();

            if ($cargo) {
                DB::beginTransaction();
                try {
                    $cargo->estado = false;
                    $cargo->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro desactivado", 'data' => $cargo], Response::HTTP_OK);
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


}
