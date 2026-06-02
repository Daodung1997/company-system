<?php

namespace App\Http\Requests\Contract;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'nullable|integer',
            'contract_code' => 'nullable|string|max:100',
            'type' => 'required|string|in:LABOR,VENDOR,CLIENT',
            'employment_type' => 'nullable|string|in:SEISHAIN,KEIYAKUSHAIN,HAKEN,ARUBAITO,FULL_TIME_VN,PART_TIME_VN',
            
            // Advanced Labor Contract Fields
            'job_title' => 'nullable|string|max:100',
            'work_location' => 'nullable|string|max:255',
            'working_hours_per_day' => 'nullable|numeric|min:0|max:24',
            'probation_salary_percentage' => 'nullable|integer|min:0|max:100',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',

            // Advanced Commercial/Partner/Vendor Contract Fields
            'partner_name' => 'nullable|string|max:255',
            'partner_tax_code' => 'nullable|string|max:50',
            'partner_representative' => 'nullable|string|max:100',
            'partner_representative_role' => 'nullable|string|max:100',
            'partner_address' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:50',
            'payment_terms' => 'nullable|string|max:100',
            'billing_cycle' => 'nullable|string|max:50',

            'is_36_agreement_applicable' => 'nullable|boolean',
            'overtime_allowance_included' => 'nullable|boolean',
            'included_overtime_hours' => 'nullable|integer|min:0',
            'probation_period_months' => 'nullable|integer|min:0',
            'insurance_enrolled' => 'nullable|string|max:255',
            'sign_date' => 'required|date|date_format:Y-m-d',
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date' => 'nullable|date|date_format:Y-m-d|after_or_equal:start_date',
            'value' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:ACTIVE,EXPIRED,TERMINATED,PENDING,PROBATION',
        ];
    }
}
