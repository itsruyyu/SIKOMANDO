<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'roles' => $this->whenLoaded(
                'roles',
                fn () => $this->roles->pluck('code')->values()
            ),
            'organizations' => $this->whenLoaded(
                'organizations',
                fn () => $this->organizations->map(fn ($org) => [
                    'id' => $org->id,
                    'name' => $org->name,
                    'registration_number' => $org->registration_number,
                    'organization_type' => $org->organization_type,
                    'email' => $org->email,
                    'phone' => $org->phone,
                    'address' => $org->address,
                ])
            ),
        ];
    }
}
