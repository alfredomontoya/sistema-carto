<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunicationResource extends JsonResource
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
            'type' => $this->type,
            'number' => $this->number,
            'year' => $this->year,
            'sequence' => $this->sequence,
            'reference' => $this->reference,
            'recipient_name' => $this->recipient_name,
            'recipient_position' => $this->recipient_position,
            'status' => $this->status,
            'file_name' => $this->file_name,
            'file_url' => $this->file_path
                ? asset('storage/'.$this->file_path)
                : null,
            'download_url' => $this->file_path
                ? route('communications.download', $this->id)
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'area' => $this->whenLoaded('area', fn () => (new AreaResource($this->area))->resolve()),
            'position' => $this->whenLoaded(
                'position',
                fn () => $this->position !== null
                    ? [
                        'id' => $this->position->id,
                        'name' => $this->position->name,
                    ]
                    : null,
            ),
            'user' => $this->whenLoaded('user', fn () => (new UserResource($this->user))->resolve()),
            'recipient_user' => $this->whenLoaded(
                'recipientUser',
                fn () => $this->recipientUser !== null
                    ? (new UserResource($this->recipientUser))->resolve()
                    : null,
            ),
            'can_edit' => $request->user()?->id === $this->user_id
                && $this->status === 'activo',
            'can_annul' => $request->user()?->id === $this->user_id
                && $this->status === 'activo',
        ];
    }
}
