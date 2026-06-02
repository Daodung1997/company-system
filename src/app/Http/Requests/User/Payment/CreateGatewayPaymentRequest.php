<?php

namespace App\Http\Requests\User\Payment;

use App\Constants\Master\Models\Payment\PaymentMethodTypeConst;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class CreateGatewayPaymentRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_method' => [
                'required',
                'string',
                Rule::in([PaymentMethodTypeConst::VNPAY, PaymentMethodTypeConst::CASH]),
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
