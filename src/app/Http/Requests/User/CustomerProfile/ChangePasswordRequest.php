<?php

namespace App\Http\Requests\User\CustomerProfile;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    use RequestTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
