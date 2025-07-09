<?php
namespace App\Http\Controllers\Publico;

use App\Http\Requests\Admin\PersonaRequest;
use App\Http\Requests\Admin\UserRequest;
use App\Http\Resources\Admin\Auth\PersonaResource;
use Exception;
use App\Models\Role;
use App\Models\Base\User;
use App\Models\Publico\Persona;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\Admin\Auth\UserResource;
use Carbon\Carbon;

class PersonaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $keyword = $request->keyword;

        $personas = Persona::where(function ($query) use ($keyword) {
            if($keyword != '' && $keyword != null) {
                $query->where('email','ilike','%'.$keyword.'%')
                      ->orWhere('docIdentidad', 'ilike', '%' . $keyword . '%')
                      ->orWhereRaw("CONCAT(nombres, ' ', ' ', apellido_paterno, ' ', apellido_materno) ilike ?", ['%' . $keyword . '%'])
                      ->orWhereRaw("CONCAT(apellido_paterno, ' ', apellido_materno, ' ', nombres, ' ') ilike ?", ['%' . $keyword . '%'])
                      ->orWhere('apellido_paterno', 'ilike', '%' . $keyword . '%')
                      ->orWhere('apellido_materno', 'ilike', '%' . $keyword . '%')
                      ->orWhere('nombres', 'ilike', '%' . $keyword . '%');
            }
//        })->whereDoesntHave('user');
        })->where('eliminado', false);
        $personas = $personas->whereDoesntHave('user.roles', function($query) {
            $query->where('name', 'SuperAdministrador');
        });

        $personas = $personas->orderBy('id', 'desc');

//        Log::alert($personas->toRawSql());

        return PersonaResource::collection($personas->paginate($request->limit));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PersonaRequest $request)
    {
        try {
            // Comenzamos una transacción para asegurarnos que ambos registros sean creados o no se realicen cambios en caso de algún error
            DB::beginTransaction();

            // Verificamos si ya existe un usuario con el DNI proporcionado
            $persona_existe = Persona::where('docIdentidad', $request->input('docIdentidad'))->exists();
            if($persona_existe) {
                return response()->json([
                    'state' => 'error',
                    'message' => 'Ya existe una persona con el DNI ' . $request->input('docIdentidad')
                ], Response::HTTP_BAD_REQUEST);
            }else{
                // Si no existe la persona, creamos una nueva
                $persona = new Persona();
                $persona->documento_tipo = $request->input('documento_tipo') ?? 'DNI';
                $persona->apellido_paterno = $request->input('apellido_paterno');
                $persona->apellido_materno = $request->input('apellido_materno');
                $persona->nombres = $request->input('nombres');
                $persona->docIdentidad = $request->input('docIdentidad');
                $persona->fecha_nacimiento = $request->input('fecha_nacimiento');
                $persona->genero = $request->input('genero');
                $persona->tipo = $request->input('tipo') ?? 'Natural';
                $persona->email = $request->email ?? $persona->email;
                $persona->direccion = $request->input('direccion');
                $persona->celular = $request->input('celular');
                $persona->save();
            }

            // Finalizamos la transacción
            DB::commit();
            return response()->json([
                'state' => 'success',
                'message' => 'Persona registrada correctamente',
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
        return new PersonaResource(Persona::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PersonaRequest $request, string $id)
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
                "message" => "Registro eliminado",
            ];
            return response()->json($response,Response::HTTP_OK);

//            $user->delete();

        } catch (Exception $e) {
            Log::error($e);
            return response()->json([],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
