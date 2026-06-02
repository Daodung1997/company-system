<?php

namespace App\Http\Resources\User\Discount;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountCheckResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'max_discount_amount' => $this->max_discount_amount,
            'min_order_amount' => $this->min_order_amount,
            'discount_amount' => $this->when(isset($this->discount_amount), $this->discount_amount),
            'final_amount' => $this->when(isset($this->final_amount), $this->final_amount),
            'is_valid' => $this->is_valid ?? true,
        ];
    }
}
