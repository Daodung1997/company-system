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
            'work_histories' => $this->whenLoaded('workHistories', function () {
                return $this->workHistories->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'department_id' => $history->department_id,
                        'job_title_id' => $history->job_title_id,
                        'department' => $history->department ? [
                            'id' => $history->department->id,
                            'name' => $history->department->name,
                        ] : null,
                        'job_title' => $history->jobTitle ? [
                            'id' => $history->jobTitle->id,
                            'name' => $history->jobTitle->name,
                        ] : null,
                        'start_date' => $history->start_date?->format('Y-m-d'),
                        'end_date' => $history->end_date?->format('Y-m-d'),
                        'salary' => $history->salary,
                        'note' => $history->note,
                    ];
                });
            }),
            'payslips' => $this->whenLoaded('payslips', function () {
                $user = auth('api')->user();
                if (!$user || ($user->id !== $this->id && !$user->hasPermissionTo('view-payslips'))) {
                    return null;
                }

                $timesheetMonths = \App\Models\Timesheet::where('employee_id', $this->id)
                    ->selectRaw("SUBSTRING(date, 1, 7) as ym")
                    ->groupBy('ym')
                    ->pluck('ym')
                    ->toArray();

                $months = array_unique(array_merge($timesheetMonths, [date('Y-m')]));
                $savedPayslips = $this->payslips->keyBy('year_month');
                $payrollList = [];

                $timesheetService = resolve(\App\Services\Timesheet\TimesheetService::class);

                foreach ($months as $ym) {
                    if ($savedPayslips->has($ym)) {
                        $payrollList[] = $savedPayslips->get($ym);
                    } else {
                        try {
                            $payrollData = $timesheetService->getPayroll($ym, 1, 1, $this->code);
                            $defaultPayroll = collect($payrollData['data'])->firstWhere('employee_id', $this->id);
                            if ($defaultPayroll) {
                                $payrollList[] = (object) $defaultPayroll;
                            }
                        } catch (\Throwable $e) {
                            // Ignore errors for individual month calculations
                        }
                    }
                }

                return collect($payrollList)->sortByDesc('year_month')->values()->map(function ($payslip) {
                    return [
                        'id' => $payslip->id ?? null,
                        'year_month' => $payslip->year_month,
                        'base_salary' => $payslip->base_salary,
                        'standard_working_days' => $payslip->standard_working_days,
                        'actual_working_days' => $payslip->actual_working_days,
                        'overtime_hours' => $payslip->overtime_hours,
                        'overtime_salary' => $payslip->overtime_salary,
                        'overtime_hours_normal' => $payslip->overtime_hours_normal,
                        'overtime_salary_normal' => $payslip->overtime_salary_normal,
                        'overtime_hours_weekend' => $payslip->overtime_hours_weekend,
                        'overtime_salary_weekend' => $payslip->overtime_salary_weekend,
                        'overtime_hours_holiday' => $payslip->overtime_hours_holiday,
                        'overtime_salary_holiday' => $payslip->overtime_salary_holiday,
                        'allowance_attendance' => $payslip->allowance_attendance,
                        'deduction_late' => $payslip->deduction_late,
                        'deduction_leave' => $payslip->deduction_leave,
                        'deduction_union' => $payslip->deduction_union ?? 50000.0,
                        'deduction_tax' => $payslip->deduction_tax,
                        'advance_payment' => $payslip->advance_payment,
                        'net_salary' => $payslip->net_salary,
                        'status' => $payslip->status ?? 'PENDING',
                        'note' => $payslip->note ?? '',
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
