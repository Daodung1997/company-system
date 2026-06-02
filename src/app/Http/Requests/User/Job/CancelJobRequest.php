<?php

namespace App\Http\Requests\User\Job;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class CancelJobRequest extends FormRequest
{
    use RequestTrait;

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'reason' => 'required|string|min:5|max:500',
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
