<?php

namespace App\Http\Resources\Admin\Job;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminJobMediaResource extends JsonResource
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
