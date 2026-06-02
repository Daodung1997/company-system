<?php

namespace App\Http\Requests\Admin\Configuration\ServiceCategory;

use App\Constants\Master\Models\ServiceCategory\ServiceCategoryStatusConst;
use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceCategoryRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:20', 'unique:m_service_categories,code'],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('m_service_categories', 'name'),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'exists:t_images,code'],
            'status' => ['required', 'string', Rule::in([ServiceCategoryStatusConst::ACTIVE, ServiceCategoryStatusConst::INACTIVE])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('m_service_categories', 'id')->where('level', \App\Constants\Master\Models\ServiceCategory\ServiceCategoryLevelConst::MAIN),
            ],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
