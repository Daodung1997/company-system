<?php

namespace App\Http\Requests\Chat;

use App\Constants\Master\Models\Conversation\ConversationTypeConst;
use App\Supports\Facades\Response\Response;
use App\Traits\RequestTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StartConversationRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'exists:m_users,id'],
            'type' => ['nullable', Rule::in(ConversationTypeConst::getValues())],
            'related_id' => ['nullable', 'integer'],
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
