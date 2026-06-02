<?php

namespace App\Http\Requests\Admin\Customer;

use App\Constants\Commons\GenderConst;
use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    use RequestTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'nullable|string|max:100',
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^0[0-9]{9}$/',
                Rule::unique('m_customer_profiles', 'phone')->ignore($this->route('customer') ?? $this->route('id'), 'user_id'),
            ],
            'address' => 'nullable|string|max:255',
            'area_id' => 'nullable|integer|exists:m_areas,id',
            'gender' => ['nullable', 'string', Rule::in(GenderConst::getValidStrings())],
            'dob' => 'nullable|date_format:Y-m-d|before_or_equal:today',
            'avatar_code' => 'nullable|string',
        ];
    }
}
