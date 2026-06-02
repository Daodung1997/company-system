<?php

namespace App\Http\Requests\User\CustomerProfile;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    use RequestTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => ['string', 'max:255'],
            'gender' => ['integer', 'in:1,2,3'],
            'dob' => ['date', 'before:today'],
            'phone' => ['string', 'max:20'],
            'area_id' => ['integer', 'exists:m_areas,id'],
            'avatar_code' => ['nullable', 'string', 'exists:t_images,code'],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
