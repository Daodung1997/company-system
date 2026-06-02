<?php

namespace App\Http\Requests\Admin\Job;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class AddJobNoteRequest extends FormRequest
{
    use RequestTrait;

    public function rules(): array
    {
        return [
            'note' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
