<?php

namespace App\Http\Requests\Common\AuthCustomer;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordCustomerRequest extends FormRequest
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
            'email' => 'required|email|max:255|exists:m_users,email',
        ];
    }
}
