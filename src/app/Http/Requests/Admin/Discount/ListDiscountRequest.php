<?php

namespace App\Http\Requests\Admin\Discount;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ListDiscountRequest extends FormRequest
{
    use RequestTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'limit' => 'nullable|integer|min:1|max:100',
            'filters' => 'nullable|array',
            'filters.status' => 'nullable|string|in:ACTIVE,INACTIVE',
            'filters.discount_type' => 'nullable|string|in:PERCENTAGE,FIXED_AMOUNT',
            'sorts' => 'nullable|array',
            'sorts.*' => 'nullable|string|in:asc,desc',
            'search' => 'nullable|array',
            'search.code' => 'nullable|string|max:255',
            'search.title' => 'nullable|string|max:255',
        ];
    }
}
