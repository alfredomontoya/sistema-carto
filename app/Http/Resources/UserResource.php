<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $roles = $this->whenLoaded('roles', fn (): Collection => $this->roles, new Collection);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'avatar_kind' => $this->avatar_kind,
            'avatar_value' => $this->avatar_value,
            'avatar_url' => $this->avatar_kind === 'upload' && $this->avatar_value
                ? asset('storage/avatars/'.$this->avatar_value)
                : null,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'roles' => $roles->pluck('name'),
            'role_ids' => $roles->pluck('id'),
            'current_position' => $this->whenLoaded(
                'currentAssignment',
                fn () => $this->currentAssignment !== null && $this->currentAssignment->position !== null
                    ? [
                        'id' => $this->currentAssignment->position->id,
                        'name' => $this->currentAssignment->position->name,
                        'code' => $this->currentAssignment->position->code,
                    ]
                    : null,
            ),
            'current_area' => $this->whenLoaded(
                'currentAssignment',
                fn () => $this->currentAssignment?->position?->area !== null
                    ? (new AreaResource($this->currentAssignment->position->area))->resolve()
                    : null,
            ),
            'can' => [
                'manage_users' => $this->can('manage users'),
                'manage_areas' => $this->can('manage areas'),
                'manage_settings' => $this->can('manage settings'),
            ],
        ];
    }
}
