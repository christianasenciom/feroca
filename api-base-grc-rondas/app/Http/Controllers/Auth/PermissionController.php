<?php

namespace App\Http\Controllers\Auth;

use App\Models\Base\User;
use DateTime;
use Exception;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Exports\PermissionExport;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\Admin\PermissionRequest;
use App\Http\Resources\Admin\Auth\PermissionResource;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(PermissionRequest $request)
    {
        //
        $permisos = Permission::query();

        if ($request->keyword != '' && $request->keyword != null) {
            $permisos = $permisos->where('name', 'ilike', '%' . $request->keyword . '%');
        }

        $permisos = $permisos->orderByDesc('created_at');

        return PermissionResource::collection($permisos->paginate($request->limit));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PermissionRequest $request)
    {
        //
        try {

            $p = Permission::create([
                'name' => $request->name,
                'guard_name' => 'api',
            ]);

            $superAdmin = Role::where('name', 'SuperAdministrador')->first();
            $superAdmin->givePermissionTo($p);

            return new PermissionResource($p);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

        $permiso = Permission::where("id", $id)->first();

        if ($permiso != null) {
            return new PermissionResource($permiso);
        } else {
            $response = [
                "state" => "error",
                "message" => "El registro no existe",
            ];
            return response()->json($response, 200);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PermissionRequest $request, string $id)
    {
        try {

            $permission = Permission::query()->findOrFail($id);
            $permission->name = $request->name;

            $permission->save();
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {

            $permission = Permission::query()->findOrFail($id);
            $permission->roles()->detach();
            $permission->delete();

            $response = [
                "state" => "success",
                "message" => "Registro eliminado",
            ];
            return response()->json($response, 200);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function all()
    {
        $permissions =  Permission::query()->orderBy("name")->get();
        return PermissionResource::collection($permissions);
    }

    public function fetchPermissionsForRole(Request $request)
    {
        try {
            $role = Role::query()->where("id", $request->role_id)
                ->with([
                    "permissions" => function ($query) {
                        $query->orderBy("name");
                    }
                ])
                ->first();
            if ($role != null) {
                return PermissionResource::collection($role->permissions);
            } else {
                $response = [
                    "state" => "error",
                    "message" => "El registro no existe",
                ];
                return response()->json($response, 200);
            }
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    public function exportar(Request $request)
    {
        //obtiene todos los parametros de busqueda del request
        $searchParams = $request->all();
        //obtiene el valor de la clave de busqueda del request
        $keyBuscar = Arr::get($searchParams, 'keyBuscar', 'keyBuscar');

        //crea un nombre para el archivo a exportar con la fecha actual
        $filename = 'exports/permisos-' . (new DateTime())->getTimestamp() . '.xlsx';

        //exporta el archivo con la funcion PermissionExport y le pasa la clave de busqueda
        Excel::store(
            (new PermissionExport)->withBuscar(
                $keyBuscar ? $keyBuscar : 'keyBuscar'
            ),
            $filename,
            'public'
        );

        //espera 2 segundos para que el archivo se cree
        sleep(2);

        //retorna la ruta para descargar el archivo, que es la ruta de la home mas la ruta del archivo
        return route("descargas") . "?rutaarchivo=" . 'public/' . $filename;
    }
}
