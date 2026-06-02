<?php

namespace App\Http\Resources\Common;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->device_id,
            'device_name' => $this->device_name,
            'device_type' => $this->device_type,
            'ip_address' => $this->ip_address,
            'last_active_at' => $this->last_active_at?->toIso8601String(),
            'is_current' => $this->isCurrentDevice(),
        ];
    }

    private function isCurrentDevice(): bool
    {
        // Compare with current request device_id if available
        return false;
    }
}
