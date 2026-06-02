<?php

namespace App\Http\Requests\User\Address;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    use RequestTrait;

    public function rules(): array
    {
        return [
            'label' => 'nullable|string|max:50',
            'receiver_name' => 'nullable|string|max:100',
            'receiver_phone' => 'nullable|string|max:20',
            'area_id' => 'nullable|exists:m_areas,id',
            'ward_id' => 'nullable|exists:m_areas,id',
            'address_detail' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_default' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
