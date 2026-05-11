<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InfoUserAuthenticatedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'persona' => $this->persona->getFullNameAttribute() ?? $this->name,
            'avatar' => $this->persona->foto ? asset('files_rondas/fotos_personas/'. $this->persona->foto) : asset('user_avatars/'. $this->avatar),
            'fecha_nacimiento' => $this->persona->fecha_nacimiento ?? '',
            'roles' => array_map(
                function ($role) {
                    return $role['name'];
                },
                $this->roles->toArray()
            ),
            'permissions' => array_map(
                function ($permission) {
                    return $permission['name'];
                },
                $this->getAllPermissions()->toArray()
            )
        ];
    }
}
