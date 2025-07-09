<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\InitNewPasswordRequest;
use App\Http\Requests\Auth\NewPasswordAulaRequest;
use App\Models\Persona;
use Exception;
use Illuminate\Http\JsonResponse;
use Spatie\Image\Image;
use App\Models\Base\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Spatie\Image\Manipulations;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Auth\SignInRequest;
use App\Http\Requests\Auth\UserInfoRequest;
use App\Http\Requests\Auth\NewPasswordRequest;
use App\Http\Resources\Auth\WebUserLoginResource;
use App\Http\Resources\Auth\InfoUserAuthenticatedResource;

class WebAuthController extends Controller
{
    //
    public function SignIn(SignInRequest $request): \Illuminate\Http\JsonResponse
    {

        try {

            $user = User::where('name', $request->name)->first();
            $hoy = Carbon::now();

            if (!$user || !Hash::check($request->password, $user->password)) {
                $response = [
                    "state" => "error",
                    "message" => "Los datos ingresados no son correctos"
                ];
                return response()->json($response, Response::HTTP_UNAUTHORIZED);
            }
            // Valida que tenga roles y permissos asignados
            if ((count($user->roles->toArray()) == 0) || (count($user->getAllPermissions()->toArray()) == 0)){
                $response = [
                    "state" => "error",
                    "message" => "El usuario no cuenta con roles asignados"
                ];
                return response()->json($response, Response::HTTP_UNAUTHORIZED);
            }

            return response()->json(new WebUserLoginResource($user), Response::HTTP_OK);
        } catch (Exception $e) {
            $response = [
                "state" => "error",
                "message" => "Se ha producido un error interno",
                "error_desc" => $e->getMessage().' '. $e->getLine()
            ];

            return response()->json($response, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function UserInfo(UserInfoRequest $request)
    {
        Log::alert(auth()->user());
        return new InfoUserAuthenticatedResource(auth()->user());
    }

    public function SingOutCurrentSession(Request $request)
    {
        try {
            // Elimina la sesion actual
            $request->user()->currentAccessToken()->delete();

            return response()->json([], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json(['error' => 'Ocurrio un error inesperado'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function SingOutAllSessions(Request $request)
    {
        try {
            // Eliminar todas las sesiones
            $request->user()->tokens()->delete();

            return response()->json([], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json(['error' => 'Ocurrio un error inesperado'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function passwordResset(NewPasswordRequest $request)
    {
        try {

            $user = Auth::user();

            if (!Hash::check($request->current_password, $user->password)) {
                $response = [
                    "state" => "error",
                    "message" => "La contraseña actual no es correcta"
                ];
                return response()->json($response, Response::HTTP_BAD_REQUEST);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json(['status' => 'success'], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json(['error' => 'Ocurrio un error inesperado'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateImage(Request $request)
    {

        $request->validate([
            'file' => 'required|file|max:2048|mimes:jpeg,jpg,png'
        ]);

        if(!$request->hasFile('file')) {
            $response = [
                "state" => "error",
                "message" => "Imagen es requerida"
            ];
            return response()->json($response, Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = Auth::user();
            $docIdentidad = $user->name;
            $old_avatar = $user->avatar;

            $file_image = $request->file('file');
            $name = $docIdentidad.time(). '.' . $file_image->getClientOriginalExtension();


            $tempPath = sys_get_temp_dir() . '/' . $file_image->getClientOriginalName();
            $tempPath = sys_get_temp_dir() . '/' . $file_image->getClientOriginalName();
            Image::load($file_image)
                  ->fit(Manipulations::FIT_MAX, 600, 600)
                  ->optimize()->save($tempPath);


            Storage::disk('user_avatars')->put($name, file_get_contents($tempPath));

            unlink($tempPath);
            if ($old_avatar) {
                Storage::disk('user_avatars')->delete($old_avatar);
            }

            $user->avatar = $name;
            $user->save();
            return response()->json(['status' => 'success','path' => asset('user_avatars/'.$name)], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json(['error' => 'Ocurrio un error inesperado'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
    public function passwordRessetInit(InitNewPasswordRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $user->password = Hash::make($request->password);
            $user->cambioPassword = true;
            $user->save();

            return response()->json(['status' => 'success'], Response::HTTP_OK);

        } catch (Exception $e) {
            Log::error($e);
            return response()->json(['error' => 'Ocurrio un error inesperado'],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
