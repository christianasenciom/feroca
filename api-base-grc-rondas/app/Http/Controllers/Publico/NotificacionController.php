<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Http\Resources\Publico\NotificacionResource;
use App\Models\Publico\Notificacion;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->keyword;

        $notificaciones = Notificacion::where(function ($query) use ($keyword) {
            if($keyword != '' && $keyword != null) {
                $query->where('denuncia_id','=','%'.$keyword.'%');
            }
        })->where('eliminado', false);

        $notificaciones = $notificaciones->orderBy('id', 'desc');

//        Log::alert($regiones->toRawSql());

        return NotificacionResource::collection($notificaciones->paginate($request->limit));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $date = Carbon::now();


                $notificacion = new Notificacion();
                $notificacion->denuncia_id = $request->input('denuncia_id');
                $notificacion->fecha = $date;
                $notificacion->destino = $request->input('destino');
                $notificacion->save();

            return response()->json([
                'state' => 'success',
                'message' => 'Notificación registrada correctamente',
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
        //return new DistritoResource(Distrito::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DistritoRequest $request, string $id)
    {

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

    }

    public function activar($id)
    {

    }

    public function desactivar($id)
    {

    }


}
