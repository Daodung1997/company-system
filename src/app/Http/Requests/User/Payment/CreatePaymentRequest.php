<?php

namespace App\Http\Requests\User\Payment;

use App\Http\Requests\BaseRequest;

class CreatePaymentRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'payment_method' => 'required|string|exists:m_payment_methods,type',
        ];
    }
}
