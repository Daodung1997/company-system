<?php

namespace App\Http\Requests\Chat;

use App\Supports\Facades\Response\Response;
use App\Traits\RequestTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UploadMessageMediaRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:jpeg,png,jpg,gif,pdf,doc,docx'],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            Response::failure($validator->errors()->toArray(), 422)
        );
    }
}
