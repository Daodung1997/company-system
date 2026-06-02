<?php

namespace App\Http\Resources\Admin\Discount;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'max_discount_amount' => $this->max_discount_amount,
            'min_order_amount' => $this->min_order_amount,
            'total_quantity' => $this->total_quantity,
            'used_quantity' => $this->used_quantity,
            'max_uses_per_user' => $this->max_uses_per_user,
            'start_date' => $this->start_date ? $this->start_date->toIso8601String() : null,
            'end_date' => $this->end_date ? $this->end_date->toIso8601String() : null,
            'status' => (int) $this->status,
            'note' => $this->note,
            'jobs' => \App\Http\Resources\User\Job\JobResource::collection($this->whenLoaded('jobs')),
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
