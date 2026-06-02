<?php

namespace App\Http\Requests\Wallet\Transaction;

use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionTypeConst;
use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class ListTransactionsRequest extends FormRequest
{
    use RequestTrait;

    public function rules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'limit' => 'nullable|integer|min:1|max:100',
            'type' => 'nullable|in:'.implode(',', WalletTransactionTypeConst::getValues()),
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'filters.type' => 'nullable|in:'.implode(',', WalletTransactionTypeConst::getValues()),
            'filters.date_from' => 'nullable|date',
            'filters.date_to' => 'nullable|date|after_or_equal:filters.date_from',
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
