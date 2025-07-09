<?php
namespace App\Http\Controllers\Auth;

use App\Http\Requests\Admin\UserRequest;
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

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $keyword = $request->keyword;

        $usuarios = User::query()->with('persona')->where('eliminado','=', false);;

        if($keyword != '' && $keyword != null) {
            $usuarios =
                $usuarios->where('email','ilike','%'.$keyword.'%')
                ->orWhereHas('persona',function($query) use ($keyword) {
                    $query->where('docIdentidad', 'ilike', '%' . $keyword . '%')
                        ->orWhereRaw("CONCAT(nombres, ' ', ' ', apellido_paterno, ' ', apellido_materno) ilike ?", ['%' . $keyword . '%'])
                        ->orWhereRaw("CONCAT(apellido_paterno, ' ', apellido_materno, ' ', nombres, ' ') ilike ?", ['%' . $keyword . '%'])
                        ->orWhere('apellido_paterno', 'ilike', '%' . $keyword . '%')
                        ->orWhere('apellido_materno', 'ilike', '%' . $keyword . '%')
                        ->orWhere('nombres', 'ilike', '%' . $keyword . '%');
                });
        }

        $usuarios = $usuarios->whereDoesntHave('roles', function($query) {
            $query->where('name', 'SuperAdministrador');
        });

        Log::alert($usuarios->toRawSql());
        return UserResource::collection($usuarios->paginate($request->limit));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request)
    {
        try {

            // Verificar si ya existe un superadministrador
            $tieneSuperAdmin = Role::where('name', 'SuperAdministrador')->first()->users()->exists();
            if ($tieneSuperAdmin && $request->input('role_id') == 1) {
                return response()->json([
                    'state' => 'error',
                    'message' => 'Ya existe un superadministrador, no se puede crear otro'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Comenzamos una transacción para asegurarnos que ambos registros sean creados o no se realicen cambios en caso de algún error
            DB::beginTransaction();

            // Verificamos si ya existe un usuario con el DNI proporcionado
            $persona_existe = Persona::where('docIdentidad', $request->input('persona.docIdentidad'))->exists();

            if($persona_existe) {
                // Si existe, verificamos si ya existe un usuario asociado a esa persona
                $persona = Persona::where('docIdentidad', $request->input('persona.docIdentidad'))->first();
                $tiene_user = User::where('persona_id', $persona->id)->exists();

                // Si ya existe un usuario, no se puede crear una nueva persona con ese DNI, lanzamos un error
                if($tiene_user){
                    return response()->json([
                        'state' => 'error',
                        'message' => 'La persona ya cuenta con un usuario'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }else{
                // Si no existe la persona, creamos una nueva
                $persona = new Persona();
                $persona->documento_tipo = $request->input('persona.documento_tipo') ?? 'DNI';
                $persona->apellido_paterno = $request->input('persona.apellido_paterno');
                $persona->apellido_materno = $request->input('persona.apellido_materno');
                $persona->nombres = $request->input('persona.nombres');
                $persona->docIdentidad = $request->input('persona.docIdentidad');
                $persona->fecha_nacimiento = $request->input('persona.fecha_nacimiento');
                $persona->genero = $request->input('persona.genero');
                $persona->tipo = $request->input('persona.tipo') ?? 'Natural';
            }

            // Las siguientes lineas son las mismas para ambas situaciones, la diferencia es que si no existe la persona, se le asigna el email proporcionado,
            // y si ya existe, se le asigna el email de la persona ya existente
            $persona->email = $request->email ?? $persona->email;
            $persona->direccion = $request->input('persona.direccion');
            $persona->celular = $request->input('persona.celular');
            $persona->save();

            // Registramos un nuevo usuario
            $usuario = new User();
            $usuario->name = $persona->docIdentidad; //usuario para que se registre en el sistema
            $usuario->email = $request->email ?? $request->input('persona.docIdentidad').'@mail.com';
            $usuario->password = Hash::make($request->password);
            $usuario->persona_id = $persona->id;
            $usuario->cambioPassword = false;
            $usuario->save();

            // Asignamos los roles seleccionados al usuario
            $roles = Role::find($request->role_id);
            $usuario->syncRoles($roles);

            // Finalizamos la transacción
            DB::commit();
            return response()->json([
                'state' => 'success',
                'message' => 'Usuario registrado correctamente',
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
        return new UserResource(User::with('persona','roles')->findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
//            Log::alert($request->input('persona.personaid'));
//            dd("stop");

            DB::beginTransaction();

            $usuario = User::findOrFail($id);
            // Actualizar PERSONA
            $persona = Persona::query()->findOrFail($usuario->persona_id);

//            $persona->apellido_paterno = $request->input('persona.apellido_paterno');
//            $persona->apellido_materno = $request->input('persona.apellido_materno');
//            $persona->nombres = $request->input('persona.nombres');
//            $persona->docIdentidad = $request->input('persona.docIdentidad');
            $persona->fecha_nacimiento = $request->input('persona.fecha_nacimiento');
//            $persona->genero = $request->input('persona.genero');
            $persona->direccion = $request->input('persona.direccion');
            $persona->celular = $request->input('persona.celular');
            $persona->email = $request->email;
            $persona->save();

            // Actualizar USUARIO
            $usuario->name = $persona->docIdentidad;
            $usuario->email = $persona->email;
            if ($request->password != null){
                $usuario->password = Hash::make($request->password);
            }
            $usuario->save();

            if($request->role_id != null){
                // Actualizar ROLES
                $roles = Role::find($request->role_id);
                $usuario->syncRoles($roles);
            }

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
