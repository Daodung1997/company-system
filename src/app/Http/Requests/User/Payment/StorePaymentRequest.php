<?php

namespace App\Http\Requests\User\Payment;

use App\Constants\Master\Models\Payment\PaymentMethodTypeConst;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends BaseRequest
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
                Rule::in(PaymentMethodTypeConst::getValues()),
                // Or use exists:m_payment_methods,type as per spec
                'exists:m_payment_methods,type',
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
