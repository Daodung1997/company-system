<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'full_name' => $this->full_name,
            'full_name_kana' => $this->full_name_kana,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status,
            'join_date' => $this->join_date?->format('Y-m-d'),
            'department' => $this->whenLoaded('department', function () {
                return [
                    'id' => $this->department->id,
                    'name' => $this->department->name,
                ];
            }),
            'job_title' => $this->whenLoaded('jobTitle', function () {
                return [
                    'id' => $this->jobTitle->id,
                    'name' => $this->jobTitle->name,
                ];
            }),
            'employee_shifts' => $this->whenLoaded('employeeShifts', function () {
                return $this->employeeShifts->map(function ($shift) {
                    return [
                        'id' => $shift->id,
                        'date' => $shift->date instanceof \Carbon\Carbon ? $shift->date->format('Y-m-d') : (is_string($shift->date) ? substr($shift->date, 0, 10) : $shift->date),
                        'working_hour_config' => $shift->workingHourConfig ? [
                            'id' => $shift->workingHourConfig->id,
                            'name' => $shift->workingHourConfig->name,
                            'start_time' => $shift->workingHourConfig->start_time,
                            'end_time' => $shift->workingHourConfig->end_time,
                            'allow_overtime' => $shift->workingHourConfig->allow_overtime,
                            'max_overtime_hours' => $shift->workingHourConfig->max_overtime_hours,
                        ] : null,
                    ];
                });
            }),
            'leave_requests' => $this->whenLoaded('leaveRequests', function () {
                return $this->leaveRequests->map(function ($leave) {
                    return [
                        'id' => $leave->id,
                        'leave_type' => $leave->leave_type,
                        'leave_session' => $leave->leave_session,
                        'start_date' => $leave->start_date instanceof \Carbon\Carbon ? $leave->start_date->format('Y-m-d') : (is_string($leave->start_date) ? substr($leave->start_date, 0, 10) : $leave->start_date),
                        'end_date' => $leave->end_date instanceof \Carbon\Carbon ? $leave->end_date->format('Y-m-d') : (is_string($leave->end_date) ? substr($leave->end_date, 0, 10) : $leave->end_date),
                        'status' => $leave->status,
                    ];
                });
            }),
            'relatives_count' => $this->relatives_count ?? 0,
        ];
    }
}
