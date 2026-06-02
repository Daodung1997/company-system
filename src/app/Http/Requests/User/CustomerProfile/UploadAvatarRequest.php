<?php

namespace App\Http\Requests\User\CustomerProfile;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class UploadAvatarRequest extends FormRequest
{
    use RequestTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'avatar' => ['required', 'image', 'max:5120'], // 5MB
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
