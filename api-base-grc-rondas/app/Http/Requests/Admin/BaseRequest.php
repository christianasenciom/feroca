<?php

namespace App\Http\Requests\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class BaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = Auth::user();
        $action = $this->route()->getActionMethod();
        $authorize = false;

        switch ($action) {
            case 'show':
            case 'index':
                $authorize = $user->hasPermissionTo('pub.bases.listar', 'api');
                break;
            case 'store':
                $authorize = $user->hasPermissionTo('pub.bases.crear', 'api');
                break;
            case 'update':
                $authorize = $user->hasPermissionTo('pub.bases.actualizar', 'api');
                break;
            case 'destroy':
                $authorize = $user->hasPermissionTo('pub.bases.eliminar', 'api');
                break;
        }
        return $authorize;
    }

    public function rules(): array
    {
        $action = $this->route()->getActionMethod();
        $reglas = [];

        switch ($action) {
            case 'store':
            case 'update':
                $reglas = [
                    'nombre_descripcion' => [
                        'required',
                        'string',
                        'max:255',
                        'min:3',
                    ],
                    'numero_partida_registral' => [
                        'nullable',
                        'string',
                        'size:8',
                        'regex:/^[0-9]{8}$/',
                    ],
                    'region_id' => [
                        'required',
                        'integer',
                        'exists:public.region,id',
                    ],
                    'provincia_id' => [
                        'required',
                        'integer',
                        'exists:public.provincia,id',
                    ],
                    'distrito_id' => [
                        'required',
                        'integer',
                        'exists:public.distrito,id',
                    ],
                    'sector_zona_id' => [
                        'nullable',
                        'integer',
                        'exists:public.sector_zona,id',
                    ],
                    'admin_id' => [
                        'nullable',
                        'integer',
                        'exists:public.personas,id', // CORRECTO: tabla 'personas'
                    ],
                ];
                break;

            case 'destroy':
                $reglas = [
                    'id' => 'required|integer',
                ];
                break;
        }

        return $reglas;
    }

    public function messages(): array
    {
        return [
            'nombre_descripcion.required' => 'El nombre/descripción de la base es obligatorio.',
            'nombre_descripcion.min' => 'El nombre debe tener al menos 3 caracteres.',
            'numero_partida_registral.size' => 'La partida registral debe tener exactamente 8 dígitos.',
            'numero_partida_registral.regex' => 'La partida registral debe contener solo números.',
            'region_id.required' => 'La región es obligatoria.',
            'region_id.exists' => 'La región seleccionada no existe.',
            'provincia_id.required' => 'La provincia es obligatoria.',
            'provincia_id.exists' => 'La provincia seleccionada no existe.',
            'distrito_id.required' => 'El distrito es obligatorio.',
            'distrito_id.exists' => 'El distrito seleccionado no existe.',
            'sector_zona_id.exists' => 'El sector seleccionado no existe.',
            'admin_id.exists' => 'El administrador seleccionado no existe.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nombre_descripcion')) {
            $this->merge([
                'nombre_descripcion' => trim($this->nombre_descripcion),
            ]);
        }

        if ($this->has('numero_partida_registral') && $this->numero_partida_registral) {
            $partida = preg_replace('/\D/', '', $this->numero_partida_registral);
            if (strlen($partida) === 8) {
                $this->merge([
                    'numero_partida_registral' => $partida,
                ]);
            }
        }

        if ($this->has('nombre') && !$this->has('nombre_descripcion')) {
            $this->merge([
                'nombre_descripcion' => trim($this->nombre),
            ]);
        }

        if ($this->has('partida_registral') && !$this->has('numero_partida_registral')) {
            $partida = preg_replace('/\D/', '', $this->partida_registral);
            if (strlen($partida) === 8) {
                $this->merge([
                    'numero_partida_registral' => $partida,
                ]);
            }
        }
    }

    public function attributes(): array
    {
        return [
            'nombre_descripcion' => 'nombre/descripción',
            'numero_partida_registral' => 'número de partida registral',
            'region_id' => 'región',
            'provincia_id' => 'provincia',
            'distrito_id' => 'distrito',
            'sector_zona_id' => 'sector/zona',
            'admin_id' => 'administrador',
        ];
    }
}
