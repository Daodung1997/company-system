<?php

namespace App\Http\Resources\Admin\Configuration;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceCategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'icon_code' => $this->icon_code,
            'icon_url' => $this->icon ? $this->icon->url : null,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'parent_id' => $this->parent_id,
            'level' => $this->level,
            'parent' => $this->whenLoaded('parent', fn () => new ServiceCategoryResource($this->parent)),
            'children' => ServiceCategoryResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
