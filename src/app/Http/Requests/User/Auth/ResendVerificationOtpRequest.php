<?php

namespace App\Http\Requests\User\Auth;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ResendVerificationOtpRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
