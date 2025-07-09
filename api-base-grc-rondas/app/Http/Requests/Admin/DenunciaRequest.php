<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DenunciaRequest extends FormRequest
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
                $authorize = $user->hasPermissionTo('pub.denuncias.listar', 'api');
                break;
            case 'store':
                $authorize = $user->hasPermissionTo('pub.denuncias.crear', 'api');
                break;
            case 'update':
                $authorize = $user->hasPermissionTo('pub.denuncias.actualizar', 'api');
                break;
            case 'destroy':
                $authorize = $user->hasPermissionTo('pub.denuncias.eliminar', 'api');
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
                    'denunciante' => 'required|exists:persona,id',
                    'tipo_conflicto' => 'required|exists:conflicto,id',
                    // 'num_denuncia' => 'required|string',
                    'libro' => 'required|estring',
                    'hoja' => 'required|estring',
                    'descripcion' => 'required|string',
                    'fecha' => 'required|date',
                    'region_id' => 'required|exists:region,id',
                    'provincia_id' => 'required|exists:provincia,id',
                    'distrito_id' => 'required|exists:distrito,id',
                    'sector_zona_id' => 'required|exists:sector,id',
                    'base_id' => 'required|exists:base,id',
                ];
                break;
            case 'update':
                $reglas = [
                    'denunciante' => 'required|exists:persona,id',
                    'tipo_conflicto' => 'required|exists:conflictopersona,id',
                    'num_denuncia' => 'required|string',
                    'libro' => 'required|estring',
                    'descripcion' => 'required|string',
                    'fecha' => 'required|date',
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
