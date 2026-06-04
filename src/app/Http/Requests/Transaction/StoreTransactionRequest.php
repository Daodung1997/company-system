<?php

namespace App\Http\Requests\Transaction;

use App\Traits\RequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    use RequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:EXPENSE,REVENUE',
            'amount' => 'required|numeric|min:0',
            'net_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'tax_rate_type' => 'nullable|string|in:VAT_8_VN,VAT_10_VN,CT_8_JP,CT_10_JP,VAT_8,VAT_10,NONE',
            'invoice_registration_number' => [
                'nullable',
                'string',
                'max:50',
            ],
            'withholding_tax' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|in:BANK_TRANSFER,CASH,CREDIT_CARD',
            'category' => 'required|string|max:100',
            'transaction_date' => 'required|date|date_format:Y-m-d',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|string|in:PAID,PENDING,CANCELLED',
            'document_ids' => 'nullable|array',
            'document_ids.*' => 'integer|exists:t_documents,id',
        ];
    }

    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
