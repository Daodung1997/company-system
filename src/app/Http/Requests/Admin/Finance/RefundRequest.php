<?php

namespace App\Http\Requests\Admin\Finance;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class RefundRequest extends FormRequest
{
    use RequestTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
