<?php

namespace App\Http\Requests\Admin\Discount;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class StoreDiscountRequest extends FormRequest
{
    use RequestTrait;

    public function rules(): array
    {
        $discountValueRule = 'required|numeric|min:0.01';
        if ($this->input('discount_type') === 'PERCENTAGE') {
            $discountValueRule .= '|max:100';
        }

        return [
            'code' => 'required|string|max:50|unique:m_discounts,code',
            'title' => 'required|string|max:255',
            'discount_type' => 'required|string|in:PERCENTAGE,FIXED_AMOUNT',
            'discount_value' => $discountValueRule,
            'max_discount_amount' => 'nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'total_quantity' => 'nullable|integer|min:1',
            'max_uses_per_user' => 'required|integer|min:1',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'status' => 'nullable|integer|in:1,2',
            'note' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
