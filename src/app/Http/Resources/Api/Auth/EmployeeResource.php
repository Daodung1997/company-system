<?php

namespace App\Http\Resources\Api\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'full_name' => $this->full_name,
            'full_name_kana' => $this->full_name_kana,
            'romaji_name' => $this->romaji_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'identity_type' => $this->identity_type,
            'identity_number' => $this->identity_number,
            'zairyu_card_expiry' => $this->zairyu_card_expiry?->format('Y-m-d'),
            'tax_code' => $this->tax_code,
            'social_insurance_code' => $this->social_insurance_code,
            'pension_number' => $this->pension_number,
            'employment_insurance_number' => $this->employment_insurance_number,
            'bank_code' => $this->bank_code,
            'bank_branch_code' => $this->bank_branch_code,
            'bank_account_type' => $this->bank_account_type,
            'bank_account_number' => $this->bank_account_number,
            'bank_account_holder_kana' => $this->bank_account_holder_kana,
            'role' => $this->role,
            'dependents_count' => $this->dependents_count,
            'address_registered' => $this->address_registered,
            'address_current' => $this->address_current,
            'status' => $this->status,
            'must_change_password' => $this->must_change_password,
            'avatar' => $this->avatar,
            'join_date' => $this->join_date?->format('Y-m-d'),
            'department' => $this->whenLoaded('department', function () {
                return [
                    'id' => $this->department->id,
                    'code' => $this->department->code,
                    'name' => $this->department->name,
                    'description' => $this->department->description,
                ];
            }),
            'created_at' => $this->created_at,
        ];
    }
}
