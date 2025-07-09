<?php

namespace App\Http\Requests\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class CargoRequest extends FormRequest
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
                $authorize = $user->hasPermissionTo('pub.cargos.listar', 'api');
                break;
            case 'store':
                $authorize = $user->hasPermissionTo('pub.cargos.crear', 'api');
                break;
            case 'update':
                $authorize = $user->hasPermissionTo('pub.cargos.actualizar', 'api');
                //dd('user'.$authorize);
                break;
            case 'destroy':
                $authorize = $user->hasPermissionTo('pub.cargos.eliminar', 'api');
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
            case 'show':
            case 'index':
                break;
            case 'store':
                $reglas = [
                    'descripcion' => 'required|string|max:255',
                ];
                break;
            case 'update':
                $reglas = [
                    'descripcion' => 'required|string|max:255',
                ];
                break;
            case 'destroy':
                $reglas = [
                    'id' => 'required',
                ];
                break;
        }
        return $reglas;
    }
}
