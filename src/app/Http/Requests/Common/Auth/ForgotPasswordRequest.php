<?php

namespace App\Http\Requests\Common\Auth;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255|exists:m_users,email',
        ];
    }

    /**
     * @throws \Exception
     */
    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
