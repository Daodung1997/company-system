<?php

namespace App\Repositories\Timesheet;

use App\Models\Timesheet;
use App\Repositories\Repository;

class TimesheetRepository extends Repository
{
    public function __construct(Timesheet $model)
    {
        parent::__construct($model);
    }

    /**
     * Get timesheet by employee and date.
     */
    public function findByEmployeeAndDate(int $employeeId, string $date): ?Timesheet
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->whereDate('date', $date)
            ->first();
    }

    /**
     * Get monthly timesheets for an employee.
     */
    public function getMonthlyTimesheets(int $employeeId, string $yearMonth)
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->where('date', 'like', "{$yearMonth}%")
            ->orderBy('date')
            ->get();
    }

    /**
     * Get all timesheets for admin/manager with filters.
     */
    public function getTimesheetsForAdmin(array $filters)
    {
        $query = $this->model->with('employee');

        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $query->whereHas('employee', function ($sub) use ($q) {
                $sub->where('full_name', 'like', "%{$q}%")
                    ->orWhere('employee_code', 'like', "%{$q}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('date', '<=', $filters['end_date']);
        }

        return $query->orderBy('date', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get statistics for all employees for a given month.
     */
    public function getMonthlyStatistics(string $yearMonth)
    {
        $configs = \App\Models\WorkingHourConfig::all();
        
        $findActiveConfig = function ($dateStr) use ($configs) {
            foreach ($configs as $config) {
                if ($config->start_date && $config->end_date) {
                    $startStr = $config->start_date instanceof \Carbon\Carbon ? $config->start_date->format('Y-m-d') : \Carbon\Carbon::parse($config->start_date)->format('Y-m-d');
                    $endStr = $config->end_date instanceof \Carbon\Carbon ? $config->end_date->format('Y-m-d') : \Carbon\Carbon::parse($config->end_date)->format('Y-m-d');
                    if ($dateStr >= $startStr && $dateStr <= $endStr) {
                        return $config;
                    }
                }
            }
            return $configs->where('is_default', true)->first() ?: (object)[
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
            ];
        };

        // Vietnam public holidays (MM-DD format)
        $publicHolidays = ['01-01', '04-30', '05-01', '09-02'];

        return \App\Models\Employee::with(['timesheets' => function ($sub) use ($yearMonth) {
            $sub->where('date', 'like', "{$yearMonth}%");
        }])->get()->map(function ($employee) use ($findActiveConfig, $yearMonth, $publicHolidays) {
            $timesheets = $employee->timesheets;
            
            // Fetch approved leave requests for the employee in the selected month
            $approvedLeaves = \App\Models\LeaveRequest::where('employee_id', $employee->id)
                ->where('status', 'APPROVED')
                ->where(function ($query) use ($yearMonth) {
                    $query->where('start_date', 'like', "{$yearMonth}%")
                          ->orWhere('end_date', 'like', "{$yearMonth}%");
                })
                ->get();

            $present = 0;
            $late = 0;
            $absent = 0;
            $totalLateHours = 0.0;
            $totalOvertimeHours = 0.0;
            $approvedLeaveDays = 0.0;
            $unapprovedAbsentDays = 0;

            // Calculate max working days in the month (exclude weekends, holidays based on saturday_mode)
            $yearMonthParts = explode('-', $yearMonth);
            $year = (int) $yearMonthParts[0];
            $month = (int) $yearMonthParts[1];
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $maxWorkingDays = 0;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $dayOfWeek = (int) date('w', strtotime($dateStr));
                $md = sprintf('%02d-%02d', $month, $day);

                // Skip public holidays
                if (in_array($md, $publicHolidays)) {
                    continue;
                }

                // Skip Sundays
                if ($dayOfWeek === 0) {
                    continue;
                }

                // Handle Saturdays based on working hour config
                if ($dayOfWeek === 6) {
                    $cfg = $findActiveConfig($dateStr);
                    $satMode = isset($cfg->saturday_mode) ? (int) $cfg->saturday_mode : 0;
                    if ($satMode === 0) {
                        continue; // Saturday off
                    }
                    // Saturday working (half or full) counts as a working day
                }

                $maxWorkingDays++;
            }

            // Calculate approved leave days for the month
            foreach ($approvedLeaves as $leave) {
                $leaveStart = $leave->start_date instanceof \Carbon\Carbon ? $leave->start_date : \Carbon\Carbon::parse($leave->start_date);
                $leaveEnd = $leave->end_date instanceof \Carbon\Carbon ? $leave->end_date : \Carbon\Carbon::parse($leave->end_date);
                $monthStart = \Carbon\Carbon::parse($yearMonth . '-01');
                $monthEnd = $monthStart->copy()->endOfMonth();

                // Clip leave range to the current month
                $effectiveStart = $leaveStart->greaterThan($monthStart) ? $leaveStart : $monthStart;
                $effectiveEnd = $leaveEnd->lessThan($monthEnd) ? $leaveEnd : $monthEnd;

                if ($effectiveStart->greaterThan($effectiveEnd)) {
                    continue;
                }

                // Count leave days within the month (only count working days)
                $cursor = $effectiveStart->copy();
                while ($cursor->lte($effectiveEnd)) {
                    $cursorStr = $cursor->format('Y-m-d');
                    $cursorDow = (int) $cursor->format('w');
                    $cursorMd = $cursor->format('m-d');

                    // Only count if it's a working day
                    $isWorkingDay = true;
                    if (in_array($cursorMd, $publicHolidays) || $cursorDow === 0) {
                        $isWorkingDay = false;
                    }
                    if ($cursorDow === 6) {
                        $cfg = $findActiveConfig($cursorStr);
                        $satMode = isset($cfg->saturday_mode) ? (int) $cfg->saturday_mode : 0;
                        if ($satMode === 0) {
                            $isWorkingDay = false;
                        }
                    }

                    if ($isWorkingDay) {
                        if ($leave->leave_session === 'ALL') {
                            $approvedLeaveDays += 1.0;
                        } else {
                            // MORNING or AFTERNOON = half day
                            $approvedLeaveDays += 0.5;
                        }
                    }

                    $cursor->addDay();
                }
            }

            foreach ($timesheets as $timesheet) {
                $dateStr = $timesheet->date instanceof \Carbon\Carbon ? $timesheet->date->format('Y-m-d') : \Carbon\Carbon::parse($timesheet->date)->format('Y-m-d');
                $cfg = $findActiveConfig($dateStr);
                $dayOfWeek = (int) date('w', strtotime($dateStr));

                // Check if employee has an approved leave request on this day
                $dayLeave = $approvedLeaves->first(function ($leave) use ($dateStr) {
                    $startStr = $leave->start_date instanceof \Carbon\Carbon ? $leave->start_date->format('Y-m-d') : \Carbon\Carbon::parse($leave->start_date)->format('Y-m-d');
                    $endStr = $leave->end_date instanceof \Carbon\Carbon ? $leave->end_date->format('Y-m-d') : \Carbon\Carbon::parse($leave->end_date)->format('Y-m-d');
                    return $dateStr >= $startStr && $dateStr <= $endStr;
                });

                if ($dayLeave && $dayLeave->leave_session === 'ALL') {
                    // Excused full-day leave
                    $present++;
                    continue;
                }

                // Determine expected end time for OT calculation
                $expectedEnd = $cfg->end_time ?? '17:30:00';
                if ($dayOfWeek === 6) {
                    $satMode = isset($cfg->saturday_mode) ? (int) $cfg->saturday_mode : 0;
                    if ($satMode === 1) {
                        $expectedEnd = '12:00:00';
                    }
                }
                if ($dayLeave && $dayLeave->leave_session === 'AFTERNOON') {
                    $expectedEnd = '12:00:00';
                }

                if ($timesheet->status === 'ABSENT') {
                    // Count as unapproved absent only if there's no approved leave
                    if (!$dayLeave) {
                        $unapprovedAbsentDays++;
                    }
                    $absent++;
                } else {
                    $isLate = false;
                    $expectedStart = $cfg->start_time; // e.g. '08:30:00'
                    
                    if ($dayLeave) {
                        if ($dayLeave->leave_session === 'MORNING') {
                            $expectedStart = '13:15:00';
                        }
                    }

                    if ($timesheet->check_in) {
                        $checkInTime = \Carbon\Carbon::parse($timesheet->check_in)->format('H:i:s');
                        if ($checkInTime > $expectedStart) {
                            $isLate = true;
                            
                            $startCarbon = \Carbon\Carbon::parse($dateStr . ' ' . $expectedStart);
                            $checkInCarbon = \Carbon\Carbon::parse($timesheet->check_in);
                            $lateMinutes = $startCarbon->diffInMinutes($checkInCarbon, false);
                            
                            if ($lateMinutes > 0) {
                                if ($lateMinutes < 5) {
                                    $penaltyHours = 0.0;
                                } elseif ($lateMinutes <= 30) {
                                    $penaltyHours = 0.5;
                                } elseif ($lateMinutes <= 60) {
                                    $penaltyHours = 1.0;
                                } else {
                                    $penaltyHours = ceil($lateMinutes / 30.0) * 0.5;
                                }
                                $totalLateHours += $penaltyHours;
                            }
                        }
                    }

                    // Calculate overtime hours (check_out after expected_end)
                    if ($timesheet->check_out) {
                        $checkOutTime = \Carbon\Carbon::parse($timesheet->check_out)->format('H:i:s');
                        if ($checkOutTime > $expectedEnd) {
                            $checkOutCarbon = \Carbon\Carbon::parse($timesheet->check_out);
                            $endCarbon = \Carbon\Carbon::parse($dateStr . ' ' . $expectedEnd);
                            $overtimeMinutes = $endCarbon->diffInMinutes($checkOutCarbon, false);
                            if ($overtimeMinutes > 0) {
                                $totalOvertimeHours += round($overtimeMinutes / 60.0, 2);
                            }
                        }
                    }

                    if ($isLate || $timesheet->status === 'LATE') {
                        $late++;
                    } else {
                        $present++;
                    }
                }
            }
            
            return [
                'employee_id' => $employee->id,
                'employee_code' => $employee->code,
                'full_name' => $employee->full_name,
                'email' => $employee->email,
                'total_present' => $present,
                'total_late' => $late,
                'total_absent' => $absent,
                'total_working_days' => $present + $late,
                'max_working_days' => $maxWorkingDays,
                'approved_leave_days' => $approvedLeaveDays,
                'unapproved_absent_days' => $unapprovedAbsentDays,
                'total_late_hours' => round($totalLateHours, 2),
                'total_overtime_hours' => round($totalOvertimeHours, 2),
                'timesheets' => $timesheets->map(function ($t) use ($findActiveConfig, $approvedLeaves) {
                    $dateStr = $t->date instanceof \Carbon\Carbon ? $t->date->format('Y-m-d') : \Carbon\Carbon::parse($t->date)->format('Y-m-d');
                    $cfg = $findActiveConfig($dateStr);

                    // Find approved leave request for daily return
                    $dayLeave = $approvedLeaves->first(function ($leave) use ($dateStr) {
                        $startStr = $leave->start_date instanceof \Carbon\Carbon ? $leave->start_date->format('Y-m-d') : \Carbon\Carbon::parse($leave->start_date)->format('Y-m-d');
                        $endStr = $leave->end_date instanceof \Carbon\Carbon ? $leave->end_date->format('Y-m-d') : \Carbon\Carbon::parse($leave->end_date)->format('Y-m-d');
                        return $dateStr >= $startStr && $dateStr <= $endStr;
                    });

                    $expectedStart = $cfg->start_time;
                    $expectedEnd = $cfg->end_time;
                    
                    if ($dayLeave) {
                        if ($dayLeave->leave_session === 'MORNING') {
                            $expectedStart = '13:15:00';
                        } elseif ($dayLeave->leave_session === 'AFTERNOON') {
                            $expectedEnd = '12:00:00';
                        }
                    }

                    return [
                        'date' => $dateStr,
                        'check_in' => $t->check_in,
                        'check_out' => $t->check_out,
                        'status' => $t->status,
                        'saturday_mode' => isset($cfg->saturday_mode) ? (int)$cfg->saturday_mode : 0,
                        'expected_start' => $expectedStart,
                        'expected_end' => $expectedEnd,
                        'leave_session' => $dayLeave ? $dayLeave->leave_session : null,
                    ];
                })->toArray(),
            ];
        });
    }
}
