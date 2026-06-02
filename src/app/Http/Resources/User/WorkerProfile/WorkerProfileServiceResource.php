<?php

namespace App\Http\Resources\User\WorkerProfile;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkerProfileServiceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->serviceCategory->id,
            'name' => $this->serviceCategory->name,
            'code' => $this->serviceCategory->code,
            'icon_url' => $this->serviceCategory->icon_url,
        ];
    }
}
