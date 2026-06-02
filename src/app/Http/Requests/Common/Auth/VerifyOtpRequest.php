<?php

namespace App\Http\Requests\Common\Auth;

use App\Constants\Commons\OtpTypeConst;
use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyOtpRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'size:6'],
            'type' => ['required', 'string', Rule::in(OtpTypeConst::getValues())],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
