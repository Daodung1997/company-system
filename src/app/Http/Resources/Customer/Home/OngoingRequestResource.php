<?php

namespace App\Http\Resources\Customer\Home;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class OngoingRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->code ?? $this->id,
            'service_name' => $this->serviceCategory?->name,
            'status' => $this->status,
            'worker' => $this->whenLoaded('worker', fn () => new UserResource($this->worker)),
        ];
    }
}
