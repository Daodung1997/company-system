<?php

namespace App\Http\Requests\User\Discount;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class CheckDiscountRequest extends FormRequest
{
    use RequestTrait;

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50',
            'price' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
