<?php

namespace App\Http\Resources\Area;

use Illuminate\Http\Resources\Json\JsonResource;

class AreaSimpleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'parent' => $this->relationLoaded('parent') && $this->parent ? new self($this->parent) : null,
        ];
    }
}
