<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebUserLoginResource extends JsonResource
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
            'token' => $this->createToken($request->name)->plainTextToken,
            'cambioPassword' => $this->cambioPassword || in_array('SuperAdministrador', $this->roles->pluck('name')->toArray())
        ];
    }
}
