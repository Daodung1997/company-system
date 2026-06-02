<?php

namespace App\Http\Requests\User\Notification;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class DeleteNotificationsRequest extends FormRequest
{
    use RequestTrait;

    public function authorize()
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer', 'exists:t_notifications,id'],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
