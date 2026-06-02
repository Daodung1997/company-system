<?php

namespace App\Http\Requests\Common\AuthWorker;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class LoginWorkerRequest extends FormRequest
{
    use RequestTrait;

    /**
     * Determine if the user is authorized to make this request.
     */
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255|exists:m_users,email',
            'password' => 'required|string',
        ];
    }
}
