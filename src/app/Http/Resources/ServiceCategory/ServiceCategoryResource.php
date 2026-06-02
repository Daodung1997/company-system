<?php

namespace App\Http\Resources\ServiceCategory;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon ? [
                'code' => $this->icon->code,
                'url' => $this->icon->url,
            ] : null,
            'parent_id' => $this->parent_id,
            'level' => $this->level,
        ];
    }
}
