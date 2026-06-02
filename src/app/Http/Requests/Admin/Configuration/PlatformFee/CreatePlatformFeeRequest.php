<?php

namespace App\Http\Requests\Admin\Configuration\PlatformFee;

use App\Constants\Master\Models\PlatformFee\PlatformFeeStatusConst;
use App\Constants\Master\Models\PlatformFee\PlatformFeeTypeConst;
use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePlatformFeeRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'fee_type' => ['required', 'string', Rule::in([PlatformFeeTypeConst::PERCENTAGE, PlatformFeeTypeConst::FIXED])],
            'amount' => ['required', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date_format:Y-m-d H:i:s', 'after_or_equal:now'],
            'end_date' => ['nullable', 'date_format:Y-m-d H:i:s', 'after:start_date'],
            'status' => ['nullable', 'string', Rule::in([PlatformFeeStatusConst::ACTIVE, PlatformFeeStatusConst::INACTIVE])],
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
