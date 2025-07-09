<?php

namespace App\Http\Requests\Admin;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Http\FormRequest;

class PermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();

        $action = $this->route()->getActionMethod();
        $authorize = false;
        switch ($action) {
            case 'show':
            case 'index':
                $authorize = $user->hasPermissionTo('auth.permisos.listar', 'api');
                break;
            case 'store':
                $authorize = $user->hasPermissionTo('auth.permisos.crear', 'api');
                break;
            case 'update':
                $authorize = $user->hasPermissionTo('auth.permisos.actualizar', 'api');
                break;
            case 'destroy':
                $authorize = $user->hasPermissionTo('auth.permisos.eliminar', 'api');
                break;
        }
        return $authorize;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $action = $this->route()->getActionMethod();
        $reglas = [];
        switch ($action) {
            case 'index':
                break;
            case 'store':
                $reglas = [
                    'name' => 'required'
                ];
                break;
            case 'destroy':
            case 'show':
                $reglas = ['id' => 'required',];
                break;
            case 'update':
                $reglas = [
                    'id' => 'required',
                    'name' => 'required'
                ];
                break;
        }
        return $reglas;
    }
}
