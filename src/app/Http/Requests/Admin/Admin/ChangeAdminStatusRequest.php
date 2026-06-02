<?php

namespace App\Http\Requests\Admin\Admin;

use App\Constants\Master\Models\Admin\AdminStatusConst;
use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeAdminStatusRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in([AdminStatusConst::ACTIVE, AdminStatusConst::INACTIVE])],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
