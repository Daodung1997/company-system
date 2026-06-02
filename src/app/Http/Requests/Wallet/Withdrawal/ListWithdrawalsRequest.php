<?php

namespace App\Http\Requests\Wallet\Withdrawal;

use App\Constants\Transaction\Models\Withdrawal\WithdrawalStatusConst;
use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ListWithdrawalsRequest extends FormRequest
{
    use RequestTrait;

    public function rules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'filters.status' => 'nullable|in:'.implode(',', WithdrawalStatusConst::getValues()),
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
