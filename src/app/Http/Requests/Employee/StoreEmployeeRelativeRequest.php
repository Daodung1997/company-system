<?php

namespace App\Http\Requests\Employee;

use App\Constants\Master\Models\Employee\EmployeeRelationshipConst;
use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRelativeRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'relationship' => ['required', 'string', Rule::in(EmployeeRelationshipConst::getValues())],
            'full_name' => ['required', 'string', 'max:150'],
            'full_name_kana' => ['nullable', 'string', 'max:150'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', Rule::in(['MALE', 'FEMALE', 'OTHER'])],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'identity_number' => ['nullable', 'string', 'max:50'],
            'occupation' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_emergency_contact' => ['nullable', 'boolean'],
            'is_dependent' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
