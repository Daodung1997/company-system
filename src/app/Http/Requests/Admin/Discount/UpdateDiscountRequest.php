<?php

namespace App\Http\Requests\Admin\Discount;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDiscountRequest extends FormRequest
{
    use RequestTrait;

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'total_quantity' => 'nullable|integer|min:1',
            'end_date' => 'required|date|after:today',
            'status' => 'required|integer|in:1,2',
            'note' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
