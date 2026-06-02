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
            'tax_rate_type' => 'nullable|string|in:VAT_8_VN,VAT_10_VN,CT_8_JP,CT_10_JP,NONE',
            'invoice_registration_number' => [
                'nullable',
                'string',
                'regex:/^T\d{13}$/',
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
        $messages = $this->renderMessageFromRule($this->rules());
        
        // Add custom message for Japan Qualified Invoice validation
        $messages['invoice_registration_number.regex'] = 'Mã số đăng ký hóa đơn điện tử Nhật Bản phải bắt đầu bằng chữ T và theo sau bởi đúng 13 chữ số (T+13 digits).';
        
        return $messages;
    }
}
