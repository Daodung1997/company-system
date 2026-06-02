<?php

namespace App\Http\Requests\Common\AuthCustomer;

use App\Constants\Commons\App;
use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordCustomerRequest extends FormRequest
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
     * @throws \Exception
     */
    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return (int) $this->first_login ? [
            'new_password' => [
                'required',
                'min:'.App::MAX_EIGHT,
                'max:'.App::MAX_TWO_HUNDRED_FIFTY_FIVE,
                'regex:'.App::REGEX_PASSWORD,
            ],
        ] : [
            'current_password' => [
                'required',
                'min:'.App::MAX_EIGHT,
                'max:'.App::MAX_TWO_HUNDRED_FIFTY_FIVE,
                'regex:'.App::REGEX_PASSWORD,
            ],
            'new_password' => [
                'required',
                'min:'.App::MAX_EIGHT,
                'max:'.App::MAX_TWO_HUNDRED_FIFTY_FIVE,
                'regex:'.App::REGEX_PASSWORD,
            ],
        ];
    }
}
