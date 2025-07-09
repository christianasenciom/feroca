<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Publico\Denunciado;
use App\Http\Requests\Admin\DenunciadoRequest;
use App\Http\Resources\Publico\DenunciadoResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class DenunciadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DenunciadoRequest $request)
    {
        try {

            DB::beginTransaction();

            // Verificamos si ya existe una persona denunciada con los datos ingresados
            // $persona_existe = Denunciado::where('apellido_paterno', $request->input('apellido_paterno'))
            //                   ->where('apellido_materno', $request->input('apellido_materno'))
            //                   ->where('nombres', $request->input('nombres'))
            //                   ->exists();
            // if($persona_existe) {
            //     return response()->json([
            //         'state' => 'error',
            //         'message' => 'Ya existe una persona con los datos ingresados'
            //     ], Response::HTTP_BAD_REQUEST);
            // }else{
                // Si no existe la persona, creamos una nueva
                $persona = new Denunciado();
                // $persona->documento_tipo = $request->input('documento_tipo') ?? 'DNI';
                $persona->apellido_paterno = $request->input('apellido_paterno');
                $persona->apellido_materno = $request->input('apellido_materno');
                $persona->nombres = $request->input('nombres');
                // $persona->docIdentidad = $request->input('docIdentidad');
                $persona->direccion = $request->input('direccion');
                $persona->celular = $request->input('celular');
                $persona->save();
            // }
            // Finalizamos la transacción
            DB::commit();
            return response()->json([
                'state' => 'success',
                'message' => 'Denunciado registrado correctamente',
                'data' => $persona
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
        return new DenunciadoResource(Denunciado::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DenunciadoRequest $request, string $id)
    {
        try {

            DB::beginTransaction();
            // Actualizar PERSONA
            $persona = Persona::query()->findOrFail($id);

            $persona->apellido_paterno = $request->input('apellido_paterno');
            $persona->apellido_materno = $request->input('apellido_materno');
            $persona->nombres = $request->input('nombres');
            $persona->docIdentidad = $request->input('docIdentidad');
            $persona->fecha_nacimiento = $request->input('fecha_nacimiento');
            $persona->genero = $request->input('genero');
            $persona->direccion = $request->input('direccion');
            $persona->celular = $request->input('celular');
            $persona->email = $request->email;
            $persona->save();

            DB::commit();
            $response = [
                "state" => "success",
                "message" => "El registro se actualizo correctamente.",
            ];
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
        try {

            $user = User::query()->findOrFail($id);
            $user->eliminado = true;
            $user->save();

            $response = [
                "state" => "success",
                "message" => "Registro eliminado"
            ];
            return response()->json($response,Response::HTTP_OK);

//            $user->delete();

        } catch (Exception $e) {
            Log::error($e);
            return response()->json([],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
