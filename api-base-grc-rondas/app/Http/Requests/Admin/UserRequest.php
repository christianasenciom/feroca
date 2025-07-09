<?php

namespace App\Http\Requests\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
                $authorize = $user->hasPermissionTo('auth.usuarios.listar', 'api');
                break;
            case 'store':
                $authorize = $user->hasPermissionTo('auth.usuarios.crear', 'api');
                break;
            case 'update':
                $authorize = $user->hasPermissionTo('auth.usuarios.actualizar', 'api');
                break;
            case 'destroy':
                $authorize = $user->hasPermissionTo('auth.usuarios.eliminar', 'api');
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
                    'persona.apellido_paterno' => 'required|string|max:255',
                    'persona.apellido_materno' => 'required|string|max:255',
                    'persona.nombres' => 'required|string|max:255',
                    'persona.docIdentidad' => 'required|max:255',
                    'email' => 'required|email|max:255',
                ];
                break;
            case 'update':
                $reglas = [
                    'id' => 'required',
                    'docIdentidad' => 'required'
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
