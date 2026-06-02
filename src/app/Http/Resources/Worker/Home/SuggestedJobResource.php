<?php

namespace App\Http\Resources\Worker\Home;

use Illuminate\Http\Resources\Json\JsonResource;

class SuggestedJobResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->code ?? $this->id,
            'service_name' => $this->serviceCategory?->name,
            'location' => $this->address,
            'expected_time' => $this->working_time,
            'created_at' => $this->created_at,
        ];
    }
}
