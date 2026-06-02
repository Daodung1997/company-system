<?php

namespace App\Http\Requests\User\Quotation;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class SubmitQuotationRequest extends FormRequest
{
    use RequestTrait;

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'price' => 'required|numeric|min:1000|max:999999999',
            'estimated_duration' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
