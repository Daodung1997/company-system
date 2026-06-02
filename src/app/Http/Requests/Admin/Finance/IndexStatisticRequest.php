<?php

namespace App\Http\Requests\Admin\Finance;

use App\Constants\Master\Models\Payment\PaymentMethodTypeConst;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class IndexStatisticRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filters.start_date' => 'nullable|date|date_format:Y-m-d',
            'filters.end_date' => 'nullable|date|date_format:Y-m-d|after_or_equal:filters.start_date',
            'filters.group_by' => 'nullable|string|in:day,week,month',
            'filters.service_id' => 'nullable|integer|exists:m_service_categories,id',
            'filters.parent_service_id' => 'nullable|integer|exists:m_service_categories,id',
            'filters.payment_method' => ['nullable', 'string', Rule::in(PaymentMethodTypeConst::getValues())],
            'filters.limit' => 'nullable|integer|min:1|max:50',
            // Legacy/Alias support
            'date_from' => 'nullable|date|date_format:Y-m-d',
            'date_to' => 'nullable|date|date_format:Y-m-d|after_or_equal:date_from',
            'group_by' => 'nullable|string|in:day,week,month',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return $this->renderMessageFromRule($this->rules());
    }
}
