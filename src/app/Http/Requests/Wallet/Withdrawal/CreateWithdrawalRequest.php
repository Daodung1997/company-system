<?php

namespace App\Http\Requests\Wallet\Withdrawal;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class CreateWithdrawalRequest extends FormRequest
{
    use RequestTrait;

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:1',
            'bank_account_id' => 'required|exists:m_bank_accounts,id',
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
