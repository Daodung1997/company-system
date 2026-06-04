<?php

namespace App\Http\Resources\Contract;

use App\Http\Resources\Api\Auth\EmployeeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'company_id' => $this->company_id,
            'company_name' => $this->company_name,
            'company_tax_code' => $this->company_tax_code,
            'company_address' => $this->company_address,
            'company_representative' => $this->company_representative,
            'company_representative_role' => $this->company_representative_role,
            'contract_code' => $this->contract_code,
            'type' => $this->type,
            'employment_type' => $this->employment_type,
            'is_36_agreement_applicable' => $this->is_36_agreement_applicable,
            'overtime_allowance_included' => $this->overtime_allowance_included,
            'included_overtime_hours' => $this->included_overtime_hours,
            'probation_period_months' => $this->probation_period_months,
            'insurance_enrolled' => $this->insurance_enrolled,
            'sign_date' => $this->sign_date ? $this->sign_date->format('Y-m-d') : null,
            'start_date' => $this->start_date ? $this->start_date->format('Y-m-d') : null,
            'end_date' => $this->end_date ? $this->end_date->format('Y-m-d') : null,
            'value' => $this->value,
            'status' => $this->status,

            // Advanced Labor Contract Fields
            'job_title' => $this->job_title,
            'work_location' => $this->work_location,
            'working_hours_per_day' => $this->working_hours_per_day,
            'probation_salary_percentage' => $this->probation_salary_percentage,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,

            // Advanced Commercial/Partner/Vendor Contract Fields
            'partner_name' => $this->partner_name,
            'partner_tax_code' => $this->partner_tax_code,
            'partner_representative' => $this->partner_representative,
            'partner_representative_role' => $this->partner_representative_role,
            'partner_address' => $this->partner_address,
            'payment_method' => $this->payment_method,
            'payment_terms' => $this->payment_terms,
            'billing_cycle' => $this->billing_cycle,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'documents' => \App\Http\Resources\Document\DocumentResource::collection($this->whenLoaded('documents')),
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toISOString() : null,
        ];
    }
}
