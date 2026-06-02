<?php

namespace App\Http\Requests\Admin\Configuration\ServiceCategory;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ReorderServiceCategoriesRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['required', 'integer', 'exists:m_service_categories,id'],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
