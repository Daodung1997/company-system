<?php

namespace App\Http\Resources\Compliance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplianceIssueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => $this->employee ? [
                'id' => $this->employee->id,
                'code' => $this->employee->code,
                'full_name' => $this->employee->full_name,
                'email' => $this->employee->email,
            ] : null,
            'contract_id' => $this->contract_id,
            'contract' => $this->contract ? [
                'id' => $this->contract->id,
                'contract_code' => $this->contract->contract_code,
                'type' => $this->contract->type,
                'start_date' => $this->contract->start_date,
                'end_date' => $this->contract->end_date,
            ] : null,
            'transaction_id' => $this->transaction_id,
            'transaction' => $this->transaction ? [
                'id' => $this->transaction->id,
                'code' => $this->transaction->code,
                'amount' => $this->transaction->amount,
                'category' => $this->transaction->category,
                'transaction_date' => $this->transaction->transaction_date,
            ] : null,
            'issue_type' => $this->issue_type,
            'severity' => $this->severity,
            'description' => $this->description,
            'status' => $this->status,
            'resolved_at' => $this->resolved_at ? $this->resolved_at->toIso8601String() : null,
            'resolved_by' => $this->resolved_by,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
