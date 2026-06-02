<?php

namespace App\Http\Requests\User\WorkerRegistration;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentsRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'documents' => ['required', 'array', 'min:1', 'max:5'],
            'documents.*' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'document_type' => ['nullable', 'string', 'in:cccd,cmnd,passport,driving_license'],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
