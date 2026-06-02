<?php

namespace App\Http\Resources\Admin\Customer;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'block_reason' => $this->block_reason,
            'email_verified_at' => $this->email_verified_at,
            'profile' => $this->customerProfile ? new CustomerProfileResource($this->customerProfile) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
