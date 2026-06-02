<?php

namespace App\Http\Requests\Admin\Admin;

use App\Constants\Master\Models\Admin\AdminStatusConst;
use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $adminId = $this->route('id');

        return [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:m_admins,email,'.$adminId,
            'status' => ['required', 'string', Rule::in([AdminStatusConst::ACTIVE, AdminStatusConst::INACTIVE])],
            'avatar_url' => 'nullable|string|max:500',
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:m_roles,id',
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
