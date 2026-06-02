<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Common\ImageSimpleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSimpleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->full_name ?? $this->name,
            'code' => $this->code,
            'avatar' => $this->avatar ? new ImageSimpleResource($this->avatar) : null,
            'is_online' => (bool) $this->is_online,
        ];
    }
}
