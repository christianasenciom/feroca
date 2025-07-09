<?php

namespace App\Http\Resources\Admin\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Admin\Auth\PersonaResource;

class UserResource extends JsonResource
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
            'persona' => new PersonaResource($this->persona),
            'roles' => $this->roles()->pluck('id'),
            'role_id' => $this->roles()->count() > 0 ? $this->roles()->first()->id : null,
            'role_name' => $this->roles()->count() > 0 ? $this->roles()->first()->name : null,
        ];
    }
}
