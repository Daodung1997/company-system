<?php

namespace App\Http\Requests\Admin\Configuration\PlatformFee;

use App\Constants\Master\Models\PlatformFee\PlatformFeeStatusConst;
use App\Constants\Master\Models\PlatformFee\PlatformFeeTypeConst;
use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformFeeRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'fee_type' => ['nullable', 'string', Rule::in([PlatformFeeTypeConst::PERCENTAGE, PlatformFeeTypeConst::FIXED])],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', Rule::in([PlatformFeeStatusConst::ACTIVE, PlatformFeeStatusConst::INACTIVE])],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
