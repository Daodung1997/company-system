<?php

namespace App\Http\Requests\Master;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'string', 'in:ACTIVE,INACTIVE'],
            'job_titles' => ['nullable', 'array'],
            'job_titles.*.id' => ['nullable', 'integer'],
            'job_titles.*.name' => ['required', 'string', 'max:255'],
            'job_titles.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
