<?php

namespace App\Http\Requests\Admin\Admin;

use App\Constants\Master\Models\Admin\AdminStatusConst;
use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:m_admins,email',
            'password' => 'required|string|min:8|max:20',
            'status' => ['required', 'string', Rule::in([AdminStatusConst::ACTIVE, AdminStatusConst::INACTIVE])],
            'avatar_url' => 'nullable|string|max:500', // assuming avatar is sent as URL or code. Let's keep max 500
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:m_roles,id',
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
