<?php

namespace App\Http\Requests\Admin\Role;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:125|unique:m_roles,name,NULL,id,guard_name,admin',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:m_permissions,id',
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
