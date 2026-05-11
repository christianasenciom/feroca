<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Publico\Conflicto;
use App\Http\Requests\Admin\ConflictoRequest;
use App\Http\Resources\Publico\ConflictoResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Auth;

class ConflictoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $keyword = $request->keyword;

        $conflictos = Conflicto::where(function ($query) use ($keyword) {
            if($keyword != '' && $keyword != null) {
                $query->where('descripcion','ilike','%'.$keyword.'%');
            }
        })->where('eliminado', false);

        $conflictos = $conflictos->orderBy('id', 'desc');

        return ConflictoResource::collection($conflictos->paginate($request->limit));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ConflictoRequest $request)
    {
        try {

            DB::beginTransaction();

            $conflicto_existe = Conflicto::where('descripcion', $request->input('descripcion'))->exists();
            if($conflicto_existe) {
                return response()->json([
                    'state' => 'error',
                    'message' => 'Ya existe un registro con la descripción ' . $request->input('descripcion')
                ], Response::HTTP_BAD_REQUEST);
            }else{
                $conflicto = new Conflicto();
                $conflicto->descripcion = $request->input('descripcion');
                $conflicto->estado = 1;
                $conflicto->save();
            }

            DB::commit();
            return response()->json([
                'state' => 'success',
                'message' => 'Conflicto registrado correctamente',
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
        return new ConflictoResource(Conflicto::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ConflictoRequest $request, string $id)
    {
        try {

            DB::beginTransaction();
            $conflicto = Conflicto::query()->where('id','=',$id)->where('eliminado',false)->first();
            if($conflicto)
            {
                $conflicto->descripcion = $request->input('descripcion');
                $conflicto->save();
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

    public function destroy(string $id)
    {
        $usuario = Auth::user()->id;
        $conflicto = Conflicto::query()->find($id);
            if ($conflicto) {
                DB::beginTransaction();
                try {
                    $conflicto->eliminado = true;
                    $conflicto->deleted_by =$usuario;
                    $conflicto->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro eliminado", 'data' => $conflicto], Response::HTTP_OK);
            } else {
                return response()->json(['error' => "Registro no encontrado"], Response::HTTP_NOT_FOUND);
            }
    }

    public function activar($id)
    {
        if (auth()->user()->hasPermissionTo('pub.conflictos.activar', 'api')) {

            $conflicto = Conflicto::query()->where('id','=',$id)->where('estado',false)->first();

            if ($conflicto) {
                DB::beginTransaction();
                try {
                    $conflicto->estado = true;
                    $conflicto->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro activado", 'data' => $conflicto], Response::HTTP_OK);
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

        if (auth()->user()->hasPermissionTo('pub.conflictos.desactivar', 'api')) {

            $conflicto = Conflicto::query()->where('id','=',$id)->where('estado',true)->first();

            if ($conflicto) {
                DB::beginTransaction();
                try {
                    $conflicto->estado = false;
                    $conflicto->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    Log::alert($ex);
                    DB::rollback();
                    return response()->json(['error' => $ex->getMessage()], Response::HTTP_BAD_REQUEST);
                }
                return response()->json(['success' => "Registro desactivado", 'data' => $conflicto], Response::HTTP_OK);
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
