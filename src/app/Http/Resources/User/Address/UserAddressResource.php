<?php

namespace App\Http\Resources\User\Address;

use App\Http\Resources\Area\AreaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'receiver_name' => $this->receiver_name,
            'receiver_phone' => $this->receiver_phone,
            'area' => $this->whenLoaded('area', fn () => new AreaResource($this->area)),
            'ward' => $this->whenLoaded('ward', fn () => $this->ward ? new AreaResource($this->ward) : null),
            'address_detail' => $this->address_detail,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_default' => $this->is_default,
            'created_at' => $this->created_at,
        ];
    }
}
