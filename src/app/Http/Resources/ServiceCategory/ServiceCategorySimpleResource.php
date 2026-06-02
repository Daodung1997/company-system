<?php

namespace App\Http\Resources\ServiceCategory;

use App\Http\Resources\Common\ImageSimpleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceCategorySimpleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'icon' => $this->icon ? new ImageSimpleResource($this->icon) : null,
            'parent' => $this->relationLoaded('parent') && $this->parent ? new self($this->parent) : null,
            'main_category' => $this->relationLoaded('parent') && $this->parent ? new self($this->parent) : null,
        ];
    }
}
