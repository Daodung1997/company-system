<?php

namespace App\Http\Resources\User\WorkerProfile;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkerProfileAreaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->area->id,
            'name' => $this->area->name,
            'code' => $this->area->code,
            'level' => $this->area->level,
            'parent' => $this->area->parent ? [
                'id' => $this->area->parent->id,
                'name' => $this->area->parent->name,
            ] : null,
        ];
    }
}
