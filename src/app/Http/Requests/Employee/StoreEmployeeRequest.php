<?php

namespace App\Http\Requests\Employee;

use App\Constants\Master\Models\Employee\EmployeeRoleConst;
use App\Constants\Master\Models\Employee\EmployeeStatusConst;
use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'job_title_id' => ['nullable', 'integer', 'exists:job_titles,id'],
            'full_name' => ['required', 'string', 'max:150'],
            'full_name_kana' => ['nullable', 'string', 'max:150'],
            'romaji_name' => ['nullable', 'string', 'max:150'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', Rule::in(['MALE', 'FEMALE', 'OTHER'])],
            'hometown' => ['nullable', 'string', 'max:255'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'ethnicity' => ['nullable', 'string', 'max:100'],
            'religion' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:employees,email'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8', 'max:50'],
            'must_change_password' => ['nullable', 'boolean'],
            'avatar' => ['nullable', 'string'],
            'identity_type' => ['nullable', 'string', Rule::in(['CCCD', 'MY_NUMBER', 'ZAIRYU_CARD', 'PASSPORT'])],
            'identity_number' => ['nullable', 'string', 'max:50', 'unique:employees,identity_number'],
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
            'join_date' => ['required', 'date'],
            'relatives' => ['nullable', 'array'],
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
            'work_histories' => ['nullable', 'array'],
            'work_histories.*.department_id' => ['required_with:work_histories', 'integer', 'exists:departments,id'],
            'work_histories.*.job_title_id' => ['nullable', 'integer', 'exists:job_titles,id'],
            'work_histories.*.start_date' => ['required_with:work_histories', 'date'],
            'work_histories.*.end_date' => ['nullable', 'date', 'after_or_equal:work_histories.*.start_date'],
            'work_histories.*.salary' => ['nullable', 'numeric', 'min:0'],
            'work_histories.*.note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
