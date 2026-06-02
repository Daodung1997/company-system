<?php

namespace App\Http\Requests\Admin\Configuration\ServiceCategory;

use App\Constants\Master\Models\ServiceCategory\ServiceCategoryStatusConst;
use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceCategoryRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('category'); // Assuming route parameter is 'category'
        // But `apiResource` usually uses 'category' if model is ServiceCategory, but we might check route param name.
        // It's often safer to use this->route()->parameter('category') or similar.
        // Laravel's unique ignore needs ID.

        return [
            'code' => ['nullable', 'string', 'max:20', Rule::unique('m_service_categories', 'code')->ignore($id)],
            'name' => ['nullable', 'string', 'max:255', Rule::unique('m_service_categories', 'name')->ignore($id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:20', Rule::exists('t_images', 'code')],
            'status' => ['nullable', 'string', Rule::in([ServiceCategoryStatusConst::ACTIVE, ServiceCategoryStatusConst::INACTIVE])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('m_service_categories', 'id')->where('level', \App\Constants\Master\Models\ServiceCategory\ServiceCategoryLevelConst::MAIN),
            ],
            'level' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
