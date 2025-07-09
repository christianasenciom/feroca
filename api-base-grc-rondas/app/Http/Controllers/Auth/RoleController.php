<?php
namespace App\Http\Controllers\Auth;

use DateTime;
use Exception;
use App\Models\Role;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\Admin\RoleRequest;
use App\Http\Resources\Admin\Auth\RoleResource;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(RoleRequest $request)
    {

        $roles = Role::query();

        if($request->keyword != '' && $request->keyword != null) {
            $roles = $roles->where('name','ilike','%'.$request->keyword.'%');
        }

        return RoleResource::collection($roles->paginate($request->limit));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request)
    {
        try {
            Role::create([
                'name' => $request->name,
                'guard_name' => 'api'
            ]);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        return new RoleResource(Role::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleRequest $request, string $id)
    {
        //
        try {

            $role = Role::query()->findOrFail($id);

            $role->name = $request->name;
            $role->save();

        } catch (Exception $e) {
            Log::error($e);
            return response()->json([],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        try {

            $role = Role::query()->findOrFail($id);
            $role->delete();
            $role->permissions()->detach();

        } catch (Exception $e) {
            Log::error($e);
            return response()->json([],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function all() {
        return RoleResource::collection(Role::query()->where('name', '!=', 'SuperAdministrador')->get());
//        return RoleResource::collection(Role::all());
    }

    public function syncRolePermissions(Request $request) {
        try {
            $role = Role::query()->findOrFail($request->role_id);
            $role->syncPermissions($request->permissions);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    public function exportar(Request $request)
    {
        $searchParams = $request->all();
        $keyBuscar = Arr::get($searchParams, 'keyBuscar', 'keyBuscar');

        $filename = 'exports/roles-' . (new DateTime())->getTimestamp(). '.xlsx';
        Excel::store(
            (new RoleExport)->withBuscar(
                $keyBuscar
            ),
            $filename,
            'public'
        );
        sleep(2);
        return route("home")."/descargas?rutaarchivo=".'public/'.$filename;

    }
}
