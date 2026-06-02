<?php

namespace App\Http\Requests\Employee;

use App\Constants\Master\Models\Employee\EmployeeRoleConst;
use App\Constants\Master\Models\Employee\EmployeeStatusConst;
use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('id');

        return [
            'department_id' => ['sometimes', 'integer', 'exists:departments,id'],
            'job_title_id' => ['sometimes', 'integer', 'exists:job_titles,id'],
            'full_name' => ['sometimes', 'string', 'max:150'],
            'full_name_kana' => ['nullable', 'string', 'max:150'],
            'romaji_name' => ['nullable', 'string', 'max:150'],
            'email' => ['sometimes', 'email', 'max:150', Rule::unique('employees', 'email')->ignore($employeeId)],
            'phone' => ['sometimes', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8', 'max:50'],
            'must_change_password' => ['nullable', 'boolean'],
            'avatar' => ['nullable', 'string'],
            'identity_type' => ['nullable', 'string', Rule::in(['CCCD', 'MY_NUMBER', 'ZAIRYU_CARD', 'PASSPORT'])],
            'identity_number' => ['nullable', 'string', 'max:50', Rule::unique('employees', 'identity_number')->ignore($employeeId)],
            'zairyu_card_expiry' => ['nullable', 'date'],
            'tax_code' => ['nullable', 'string', 'max:50'],
            'social_insurance_code' => ['nullable', 'string', 'max:50'],
            'pension_number' => ['nullable', 'string', 'max:50'],
            'employment_insurance_number' => ['nullable', 'string', 'max:50'],
            'bank_code' => ['nullable', 'string', 'max:10'],
            'bank_branch_code' => ['nullable', 'string', 'max:10'],
            'bank_account_type' => ['nullable', 'string', 'max:50'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_holder_kana' => ['nullable', 'string', 'max:150'],
            'role' => ['nullable', 'string', Rule::in(EmployeeRoleConst::getValues())],
            'dependents_count' => ['nullable', 'integer', 'min:0'],
            'address_registered' => ['nullable', 'string', 'max:500'],
            'address_current' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', Rule::in(EmployeeStatusConst::getValues())],
            'join_date' => ['sometimes', 'date'],
            'relatives' => ['nullable', 'array'],
            'relatives.*.id' => ['nullable', 'integer', 'exists:employee_relatives,id'],
            'relatives.*.relationship' => ['required_with:relatives', 'string', Rule::in(['SPOUSE', 'CHILD', 'PARENT', 'SIBLING', 'OTHER'])],
            'relatives.*.full_name' => ['required_with:relatives', 'string', 'max:150'],
            'relatives.*.full_name_kana' => ['nullable', 'string', 'max:150'],
            'relatives.*.date_of_birth' => ['nullable', 'date'],
            'relatives.*.gender' => ['nullable', 'string', Rule::in(['MALE', 'FEMALE', 'OTHER'])],
            'relatives.*.phone' => ['required_with:relatives', 'string', 'max:20'],
            'relatives.*.email' => ['nullable', 'email', 'max:150'],
            'relatives.*.identity_number' => ['nullable', 'string', 'max:50'],
            'relatives.*.occupation' => ['nullable', 'string', 'max:150'],
            'relatives.*.address' => ['nullable', 'string', 'max:500'],
            'relatives.*.is_emergency_contact' => ['nullable', 'boolean'],
            'relatives.*.is_dependent' => ['nullable', 'boolean'],
            'relatives.*.notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
