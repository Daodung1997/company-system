<?php

namespace App\Http\Requests\Area;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ListAreaRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'level' => ['nullable', 'integer', 'in:1,2,3'],
            'parent_id' => ['nullable', 'integer', 'exists:m_areas,id'],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }

    /**
     * Validate that at least one of level or parent_id is present.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('level') && ! $this->filled('parent_id')) {
                $validator->errors()->add('level', 'level.required_without');
            }
        });
    }
}
