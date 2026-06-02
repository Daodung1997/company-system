<?php

namespace App\Http\Requests\Wallet\BankAccount;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class CreateBankAccountRequest extends FormRequest
{
    use RequestTrait;

    public function rules(): array
    {
        return [
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|digits_between:8,20',
            'account_name' => 'required|string|min:2|max:100',
            'branch' => 'nullable|string|max:200',
            'is_default' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
