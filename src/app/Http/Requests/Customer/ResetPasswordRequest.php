<?php

namespace App\Http\Requests\Customer;

use App\Constants\Commons\App;
use App\Constants\Master\Models\Customer\CustomerColumn;
use App\Constants\Master\Models\PasswordReset\PasswordResetColumn;
use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    use RequestTrait;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            PasswordResetColumn::TOKEN => 'required|string',
            CustomerColumn::EMAIL => 'required|email|max:255|exists:m_customers,email',
            CustomerColumn::PASSWORD => 'required|string|min:8|max:255|regex:'.App::REGEX_PASSWORD,
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
