<?php

namespace App\Http\Requests\Common\Auth;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class RegisterWorkerRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:20',
                'regex:/[a-z]/',      // at least one lowercase letter
                'regex:/[A-Z]/',      // at least one uppercase letter
                'regex:/[0-9]/',      // at least one digit
                'regex:/[@$!%*#?&]/', // at least one special character
            ],
            'id_card_number' => ['required', 'string', 'max:20', 'unique:worker_profiles,id_card_number'],
            'id_card_front' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'id_card_back' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'selfie' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
