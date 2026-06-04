<?php

namespace App\Http\Requests\Api\Auth;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'min:6'],
            'password'         => [
                'required',
                'string',
                'min:8',
                'max:50',
                'regex:/[a-z]/',      // ít nhất 1 chữ thường
                'regex:/[A-Z]/',      // ít nhất 1 chữ hoa
                'regex:/[0-9]/',      // ít nhất 1 chữ số
                'regex:/[@$!%*?&#^()_\-+=\[\]{}|\\:;"\'<>,.\\/~`]/', // ít nhất 1 ký tự đặc biệt
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        $passwordMessages = [
            'password.min'   => 'Mật khẩu mới phải chứa ít nhất 8 ký tự.',
            'password.max'   => 'Mật khẩu mới không được vượt quá 50 ký tự.',
            'password.regex' => 'Mật khẩu mới phải chứa ít nhất 1 chữ hoa, 1 chữ thường, 1 chữ số và 1 ký tự đặc biệt.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ];

        return array_merge(
            $this->renderMessageFromRule($this->rules()),
            $passwordMessages
        );
    }
}
