<?php

namespace App\Http\Resources\Wallet;

use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalLogResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'status' => $this->status,
            'payload' => $this->payload,
            'time' => $this->created_at,
            'created_at' => $this->created_at,
        ];
    }
}
