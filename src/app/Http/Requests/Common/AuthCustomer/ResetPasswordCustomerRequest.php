<?php

namespace App\Http\Requests\Common\AuthCustomer;

use App\Constants\Commons\App;
use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordCustomerRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @throws \Exception
     */
    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }

    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'email' => 'required|email|max:255|exists:m_users,email',
            'password' => 'required|min:8|max:255|regex:'.App::REGEX_PASSWORD,
        ];
    }
}
