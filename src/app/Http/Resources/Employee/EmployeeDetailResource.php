<?php

namespace App\Http\Resources\Employee;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'full_name' => $this->full_name,
            'full_name_kana' => $this->full_name_kana,
            'romaji_name' => $this->romaji_name,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,
            'hometown' => $this->hometown,
            'place_of_birth' => $this->place_of_birth,
            'nationality' => $this->nationality,
            'ethnicity' => $this->ethnicity,
            'religion' => $this->religion,
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
            'job_title' => $this->whenLoaded('jobTitle', function () {
                return [
                    'id' => $this->jobTitle->id,
                    'code' => $this->jobTitle->code,
                    'name' => $this->jobTitle->name,
                    'description' => $this->jobTitle->description,
                ];
            }),
            'relatives' => EmployeeRelativeResource::collection($this->whenLoaded('relatives')),
            'contracts' => $this->whenLoaded('contracts', function () {
                return $this->contracts->map(function ($contract) {
                    return [
                        'id' => $contract->id,
                        'contract_code' => $contract->contract_code,
                        'type' => $contract->type,
                        'employment_type' => $contract->employment_type,
                        'sign_date' => $contract->sign_date?->format('Y-m-d'),
                        'start_date' => $contract->start_date?->format('Y-m-d'),
                        'end_date' => $contract->end_date?->format('Y-m-d'),
                        'value' => $contract->value,
                        'status' => $contract->status,
                        'documents' => $contract->documents->map(function ($doc) {
                            return [
                                'id' => $doc->id,
                                'title' => $doc->origin_name ?: $doc->title,
                                'file_path' => $doc->file_path,
                                'url' => $doc->url,
                                'extension' => $doc->extension,
                                'filesize' => $doc->filesize,
                            ];
                        }),
                    ];
                });
            }),
            'documents' => $this->whenLoaded('documents', function () {
                return $this->documents->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'title' => $doc->origin_name ?: $doc->title,
                        'file_path' => $doc->file_path,
                        'url' => $doc->url,
                        'extension' => $doc->extension,
                        'filesize' => $doc->filesize,
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
