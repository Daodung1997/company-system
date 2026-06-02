<?php

namespace App\Http\Requests\Chat;

use App\Constants\Master\Models\Message\MessageTypeConst;
use App\Supports\Facades\Response\Response;
use App\Traits\RequestTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreMessageRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required_if:type,'.MessageTypeConst::TEXT, 'nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::in(MessageTypeConst::getValues())],
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
