<?php

namespace App\Http\Requests\Admin\Customer;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ListCustomerRequest extends FormRequest
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
            'filters.status' => 'nullable|string|in:active,blocked,pending_verification',
            'filters.name' => 'nullable|string|max:255',
            'filters.email' => 'nullable|string|max:255',
            'sorts' => 'nullable|array',
            'sorts.*' => 'nullable|string|in:asc,desc',
            'search' => 'nullable|array',
            'search.name' => 'nullable|string|max:255',
            'search.email' => 'nullable|string|max:255',
            'search.code' => 'nullable|string|max:255',
            'search.phone' => 'nullable|string|max:255',
        ];
    }
}
