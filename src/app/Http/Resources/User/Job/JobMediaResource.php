<?php

namespace App\Http\Resources\User\Job;

use Illuminate\Http\Resources\Json\JsonResource;

class JobMediaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'type' => $this->type,
        ];
    }
}
