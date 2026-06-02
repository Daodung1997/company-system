<?php

namespace App\Http\Resources\Area;

use Illuminate\Http\Resources\Json\JsonResource;

class AreaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'level' => $this->level,
            'parent_id' => $this->parent_id,
        ];
    }
}
