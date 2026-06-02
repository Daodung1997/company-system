<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeRelativeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'employee_id' => $this->employee_id,
            'relationship' => $this->relationship,
            'full_name' => $this->full_name,
            'full_name_kana' => $this->full_name_kana,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,
            'phone' => $this->phone,
            'email' => $this->email,
            'identity_number' => $this->identity_number,
            'occupation' => $this->occupation,
            'address' => $this->address,
            'is_emergency_contact' => $this->is_emergency_contact,
            'is_dependent' => $this->is_dependent,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
