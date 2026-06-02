<?php

namespace App\Http\Requests\Webhook;

use App\Http\Requests\BaseRequest;

class VnpayIpnRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vnp_TxnRef' => ['required', 'string'],
            'vnp_ResponseCode' => ['required', 'string'],
            'vnp_Amount' => ['required'],
            'vnp_SecureHash' => ['required', 'string'],
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
