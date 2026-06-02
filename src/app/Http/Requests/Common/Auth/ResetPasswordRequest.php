<?php

namespace App\Http\Requests\Common\Auth;

use App\Constants\Commons\App;
use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        return [
            'token' => 'required|string',
            'email' => 'required|email|max:255|exists:m_users,email',
            'password' => 'required|min:8|max:255|regex:'.App::REGEX_PASSWORD,
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
