<?php

namespace App\Http\Requests\User\Notification;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class RegisterDeviceTokenRequest extends FormRequest
{
    use RequestTrait;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'fcm_token' => 'required|string|max:255',
            'device_id' => 'required|string|max:100',
            'device_type' => 'nullable|string|in:android,ios,web',
            'device_name' => 'nullable|string|max:100',
        ];
    }

    public function messages()
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
