<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);

        return [
            'id' => $this->id,
            'email' => $this->email,
            'persona_dni' => $this->persona ? $this->persona->docIdentidad : '',
            'persona_full_name' => $this->persona ? $this->persona->full_name : '',
            'rol_name' => $this->roles ? ( $this->roles->first() ? $this->roles->first()->name : '' ) : '',
        ];

    }
}
