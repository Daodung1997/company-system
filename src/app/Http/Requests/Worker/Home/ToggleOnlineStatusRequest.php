<?php

namespace App\Http\Requests\Worker\Home;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ToggleOnlineStatusRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_online' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
