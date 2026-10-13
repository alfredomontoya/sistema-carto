<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunicationTypeResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'digits' => $this->digits,
            'format_template' => $this->format_template,
            'reset_annually' => $this->reset_annually,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
