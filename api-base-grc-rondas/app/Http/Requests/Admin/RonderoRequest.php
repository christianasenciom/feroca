<?php

namespace App\Http\Requests\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class RonderoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $action = $this->route()->getActionMethod();
        $authorize = false;
        switch ($action) {
            case 'show':
            case 'index':
                $authorize = $user->hasPermissionTo('pub.rondero.listar', 'api');
                break;
            case 'store':
                $authorize = $user->hasPermissionTo('pub.rondero.crear', 'api');
                break;
            case 'update':
                $authorize = $user->hasPermissionTo('pub.rondero.actualizar', 'api');
                break;
            case 'destroy':
                $authorize = $user->hasPermissionTo('pub.rondero.eliminar', 'api');
                break;
        }
        return $authorize;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $action = $this->route()->getActionMethod();
        
        switch ($action) {
            case 'store':
                return [
                    'docIdentidad' => 'required|string|size:8',
                    'nombres' => 'required|string|max:255',
                    'apellido_paterno' => 'required|string|max:255',
                    'apellido_materno' => 'required|string|max:255',
                    'fecha_inicio' => 'required|date',
                    'fecha_cese' => 'nullable|date',
                    'region_id' => 'required|exists:region,id',
                    'provincia_id' => 'required|exists:provincia,id',
                    'distrito_id' => 'required|exists:distrito,id',
                    'base_id' => 'required|exists:base,id',
                    'sector_zona_id' => 'nullable|exists:sector_zona,id',
                    'genero' => 'nullable|string|in:MASCULINO,FEMENINO',
                    'celular' => 'nullable|string|max:15',
                    'direccion' => 'nullable|string|max:500',
                    'email' => 'nullable|email|max:255',
                    'foto' => 'nullable|string',
                ];
                
            case 'update':
                return [
                    'fecha_inicio' => 'nullable|date',
                    'fecha_cese' => 'nullable|date',
                    'region_id' => 'nullable|exists:region,id',
                    'provincia_id' => 'nullable|exists:provincia,id',
                    'distrito_id' => 'nullable|exists:distrito,id',
                    'base_id' => 'nullable|exists:base,id',
                    'sector_zona_id' => 'nullable|exists:sector_zona,id',
                    'persona.fecha_nacimiento' => 'nullable|date',
                    'persona.genero' => 'nullable|string|in:MASCULINO,FEMENINO',
                    'persona.direccion' => 'nullable|string|max:500',
                    'persona.celular' => 'nullable|string|max:15',
                    'persona.email' => 'nullable|email|max:255',
                    'persona.foto' => 'nullable|string',
                ];
                
            case 'destroy':
                return [];
                
            default:
                return [];
        }
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'docIdentidad.required' => 'El DNI es requerido',
            'docIdentidad.size' => 'El DNI debe tener 8 dígitos',
            'nombres.required' => 'Los nombres son requeridos',
            'apellido_paterno.required' => 'El apellido paterno es requerido',
            'apellido_materno.required' => 'El apellido materno es requerido',
            'fecha_inicio.required' => 'La fecha de inicio es requerida',
            'region_id.required' => 'La región es requerida',
            'provincia_id.required' => 'La provincia es requerida',
            'distrito_id.required' => 'El distrito es requerido',
            'base_id.required' => 'La base es requerida',
            'region_id.exists' => 'La región seleccionada no existe',
            'provincia_id.exists' => 'La provincia seleccionada no existe',
            'distrito_id.exists' => 'El distrito seleccionado no existe',
            'base_id.exists' => 'La base seleccionada no existe',
            'persona.email.email' => 'El formato del email no es válido',
        ];
    }
}