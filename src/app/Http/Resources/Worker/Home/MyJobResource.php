<?php

namespace App\Http\Resources\Worker\Home;

use Illuminate\Http\Resources\Json\JsonResource;

class MyJobResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->code ?? $this->id,
            'service_name' => $this->serviceCategory?->name,
            'customer_name' => $this->customer?->name,
            'status' => $this->status,
            'expected_time' => $this->working_time,
        ];
    }
}
