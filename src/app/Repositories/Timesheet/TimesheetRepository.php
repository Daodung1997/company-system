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
        $query = $this->model->with([
            'employee.employeeShifts',
            'employee.employeeShifts.workingHourConfig',
            'employee.leaveRequests' => function ($sub) {
                $sub->where('status', 'APPROVED');
            }
        ]);

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

        $paginator = $query->orderBy('date', 'desc')->paginate($filters['per_page'] ?? 15);

        $configs = \App\Models\WorkingHourConfig::all();
        $findActiveConfig = function ($dateStr) use ($configs) {
            return $configs->where('is_default', true)->first() ?: (object)[
                'start_time' => '08:30:00',
                'end_time' => '17:30:00',
            ];
        };

        // Vietnam public holidays (MM-DD format)
        $publicHolidays = ['01-01', '04-30', '05-01', '09-02'];

        $paginator->getCollection()->transform(function ($timesheet) use ($findActiveConfig, $publicHolidays) {
            $dateStr = $timesheet->date instanceof \Carbon\Carbon ? $timesheet->date->format('Y-m-d') : \Carbon\Carbon::parse($timesheet->date)->format('Y-m-d');
            $dayOfWeek = (int) date('w', strtotime($dateStr));
            $md = sprintf('%02d-%02d', (int)date('m', strtotime($dateStr)), (int)date('d', strtotime($dateStr)));

            // Eager-loaded employee shifts
            $empShifts = $timesheet->employee ? $timesheet->employee->employeeShifts : collect();
            $empShift = $empShifts->first(function ($es) use ($dateStr) {
                $esDate = $es->date instanceof \Carbon\Carbon ? $es->date->format('Y-m-d') : \Carbon\Carbon::parse($es->date)->format('Y-m-d');
                return $esDate === $dateStr;
            });

            // Eager-loaded approved leave requests
            $approvedLeaves = $timesheet->employee ? $timesheet->employee->leaveRequests : collect();
            $dayLeave = $approvedLeaves->first(function ($leave) use ($dateStr) {
                $startStr = $leave->start_date instanceof \Carbon\Carbon ? $leave->start_date->format('Y-m-d') : \Carbon\Carbon::parse($leave->start_date)->format('Y-m-d');
                $endStr = $leave->end_date instanceof \Carbon\Carbon ? $leave->end_date->format('Y-m-d') : \Carbon\Carbon::parse($leave->end_date)->format('Y-m-d');
                return $dateStr >= $startStr && $dateStr <= $endStr;
            });

            if ($empShift && $empShift->workingHourConfig) {
                $expectedStart = $empShift->workingHourConfig->start_time;
                $expectedEnd = $empShift->workingHourConfig->end_time;
            } else {
                if ($dayOfWeek === 0 || $dayOfWeek === 6 || in_array($md, $publicHolidays)) {
                    $expectedStart = null;
                    $expectedEnd = null;
                } else {
                    $cfg = $findActiveConfig($dateStr);
                    $expectedStart = $cfg->start_time;
                    $expectedEnd = $cfg->end_time;
                }
            }

            if ($dayLeave) {
                if ($dayLeave->leave_session === 'ALL') {
                    $expectedStart = null;
                    $expectedEnd = null;
                } elseif ($dayLeave->leave_session === 'MORNING') {
                    $expectedStart = '13:15:00';
                } elseif ($dayLeave->leave_session === 'AFTERNOON') {
                    $expectedEnd = '12:00:00';
                }
            }

            // Calculate diff
            $checkoutDiff = null;
            if ($timesheet->check_out && $expectedEnd) {
                $tz = $timesheet->timezone ?: 'Asia/Ho_Chi_Minh';
                $checkOutCarbon = \Carbon\Carbon::parse($timesheet->check_out)->setTimezone($tz);
                // Extract only time from check_out and build a Carbon date on dateStr
                $checkOutTime = $checkOutCarbon->format('H:i:s');
                $checkOutOnDay = \Carbon\Carbon::parse($dateStr . ' ' . $checkOutTime, $tz);
                $expectedEndCarbon = \Carbon\Carbon::parse($dateStr . ' ' . $expectedEnd, $tz);
                
                // Difference in minutes (positive means extra time worked, negative means left early)
                $checkoutDiff = $expectedEndCarbon->diffInMinutes($checkOutOnDay, false);
            }

            $timesheet->expected_start = $expectedStart;
            $timesheet->expected_end = $expectedEnd;
            $timesheet->checkout_diff = $checkoutDiff;

            return $timesheet;
        });

        return $paginator;
    }

    /**
     * Get statistics for all employees for a given month.
     */
    public function getMonthlyStatistics(string $yearMonth, int $page = 1, int $perPage = 15, ?string $search = null)
    {
        $configs = \App\Models\WorkingHourConfig::all();
        
        $findActiveConfig = function ($dateStr) use ($configs) {
            return $configs->where('is_default', true)->first() ?: (object)[
                'start_time' => '08:30:00',
                'end_time' => '17:30:00',
            ];
        };

        // Vietnam public holidays (MM-DD format)
        $publicHolidays = ['01-01', '04-30', '05-01', '09-02'];

        $employeeQuery = \App\Models\Employee::with([
            'timesheets' => function ($sub) use ($yearMonth) {
                $sub->where('date', 'like', "{$yearMonth}%");
            },
            'employeeShifts' => function ($sub) use ($yearMonth) {
                $sub->where('date', 'like', "{$yearMonth}%");
            },
            'employeeShifts.workingHourConfig'
        ])->orderBy('id', 'desc');

        if (!empty($search)) {
            $employeeQuery->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $paginatedEmployees = $employeeQuery->paginate($perPage, ['*'], 'page', $page);

        $mappedData = $paginatedEmployees->getCollection()->map(function ($employee) use ($findActiveConfig, $yearMonth, $publicHolidays) {
            $timesheets = $employee->timesheets;
            $empShifts = $employee->employeeShifts;
            
            // Fetch approved leave requests for the employee in the selected month
            $approvedLeaves = \App\Models\LeaveRequest::where('employee_id', $employee->id)
                ->where('status', 'APPROVED')
                ->where(function ($query) use ($yearMonth) {
                    $query->where('start_date', 'like', "{$yearMonth}%")
                          ->orWhere('end_date', 'like', "{$yearMonth}%");
                })
                ->get();

            $present = 0.0;
            $late = 0.0;
            $absent = 0.0;
            $totalLateHours = 0.0;
            $totalOvertimeHours = 0.0;
            $overtimeHoursNormal = 0.0;
            $overtimeHoursWeekend = 0.0;
            $overtimeHoursHoliday = 0.0;
            $approvedLeaveDays = 0.0;
            $unapprovedAbsentDays = 0.0;

            // Calculate dates in month
            $yearMonthParts = explode('-', $yearMonth);
            $year = (int) $yearMonthParts[0];
            $month = (int) $yearMonthParts[1];
            $daysInMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;
            $maxWorkingDays = 0;

            $dailyTimesheetsMap = [];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $dayOfWeek = (int) date('w', strtotime($dateStr));
                $md = sprintf('%02d-%02d', $month, $day);

                // Find if employee has a shift assigned on this date
                $empShift = $empShifts->first(function ($es) use ($dateStr) {
                    $esDate = $es->date instanceof \Carbon\Carbon ? $es->date->format('Y-m-d') : \Carbon\Carbon::parse($es->date)->format('Y-m-d');
                    return $esDate === $dateStr;
                });

                $isWorkingDay = false;
                if ($empShift) {
                    $isWorkingDay = true;
                } elseif (!in_array($md, $publicHolidays) && $dayOfWeek !== 0 && $dayOfWeek !== 6) {
                    $isWorkingDay = true;
                }

                if ($isWorkingDay) {
                    $maxWorkingDays++;
                }

                // Find timesheet record from DB if any
                $t = $timesheets->first(function ($ts) use ($dateStr) {
                    $tsDate = $ts->date instanceof \Carbon\Carbon ? $ts->date->format('Y-m-d') : \Carbon\Carbon::parse($ts->date)->format('Y-m-d');
                    return $tsDate === $dateStr;
                });

                // Find approved leave request for daily return
                $dayLeave = $approvedLeaves->first(function ($leave) use ($dateStr) {
                    $startStr = $leave->start_date instanceof \Carbon\Carbon ? $leave->start_date->format('Y-m-d') : \Carbon\Carbon::parse($leave->start_date)->format('Y-m-d');
                    $endStr = $leave->end_date instanceof \Carbon\Carbon ? $leave->end_date->format('Y-m-d') : \Carbon\Carbon::parse($leave->end_date)->format('Y-m-d');
                    return $dateStr >= $startStr && $dateStr <= $endStr;
                });

                if ($empShift && $empShift->workingHourConfig) {
                    $expectedStart = $empShift->workingHourConfig->start_time;
                    $expectedEnd = $empShift->workingHourConfig->end_time;
                    $shiftName = $empShift->workingHourConfig->name;
                    $allowOvertime = $empShift->workingHourConfig->allow_overtime;
                    $maxOvertimeHours = $empShift->workingHourConfig->max_overtime_hours;
                } else {
                    $cfg = $findActiveConfig($dateStr);
                    if ($dayOfWeek === 0 || $dayOfWeek === 6) {
                        $expectedStart = null;
                        $expectedEnd = null;
                        $shiftName = null;
                        $allowOvertime = false;
                        $maxOvertimeHours = null;
                    } else {
                        $expectedStart = $cfg->start_time;
                        $expectedEnd = $cfg->end_time;
                        $shiftName = null;
                        $allowOvertime = $cfg->allow_overtime;
                        $maxOvertimeHours = $cfg->max_overtime_hours;
                    }
                }

                if ($dayLeave) {
                    if ($dayLeave->leave_session === 'ALL') {
                        $approvedLeaveDays += 1.0;
                    } else {
                        $approvedLeaveDays += 0.5;
                        if ($dayLeave->leave_session === 'MORNING') {
                            $expectedStart = '13:15:00';
                        } elseif ($dayLeave->leave_session === 'AFTERNOON') {
                            $expectedEnd = '12:00:00';
                        }
                    }
                }

                // Daily metrics calculation
                if ($isWorkingDay) {
                    if ($dayLeave && $dayLeave->leave_session === 'ALL') {
                        // Full day approved leave: no absent penalty, counted as present (excused)
                        $present += 1.0;
                    } elseif ($t) {
                        if ($t->status === 'ABSENT') {
                            $absent += ($dayLeave ? 0.5 : 1.0);
                            if (!$dayLeave) {
                                $unapprovedAbsentDays += 1.0;
                            } else {
                                $unapprovedAbsentDays += 0.5;
                            }
                        } else {
                            $isLate = false;
                            $dayWeight = $dayLeave ? 0.5 : 1.0;

                            if ($t->check_in) {
                                $tz = $t->timezone ?: 'Asia/Ho_Chi_Minh';
                                $checkInCarbon = \Carbon\Carbon::parse($t->check_in)->setTimezone($tz);
                                $checkInTime = $checkInCarbon->format('H:i:s');
                                if ($expectedStart && $checkInTime > $expectedStart) {
                                    $isLate = true;
                                    $startCarbon = \Carbon\Carbon::parse($dateStr . ' ' . $expectedStart, $tz);
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

                            // Calculate overtime
                            $dailyOt = 0.0;
                            if ($t->check_out) {
                                $tz = $t->timezone ?: 'Asia/Ho_Chi_Minh';
                                $checkOutCarbon = \Carbon\Carbon::parse($t->check_out)->setTimezone($tz);
                                if ($expectedEnd) {
                                    $checkOutTime = $checkOutCarbon->format('H:i:s');
                                    if ($checkOutTime > $expectedEnd) {
                                        $endCarbon = \Carbon\Carbon::parse($dateStr . ' ' . $expectedEnd, $tz);
                                        $overtimeMinutes = $endCarbon->diffInMinutes($checkOutCarbon, false);
                                        if ($overtimeMinutes > 0) {
                                            $dailyOt = round($overtimeMinutes / 60.0, 2);
                                            if (!$allowOvertime) {
                                                $dailyOt = 0.0;
                                            } else {
                                                if (!is_null($maxOvertimeHours)) {
                                                    $dailyOt = min($dailyOt, (double)$maxOvertimeHours);
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    // Weekend or holiday shift, if they checked in and out, count the whole duration
                                    if ($t->check_in) {
                                        $checkInCarbon = \Carbon\Carbon::parse($t->check_in)->setTimezone($tz);
                                        $overtimeMinutes = $checkInCarbon->diffInMinutes($checkOutCarbon, false);
                                        if ($overtimeMinutes > 0) {
                                            if ($overtimeMinutes > 240) {
                                                $overtimeMinutes -= 60; // Lunch break
                                            }
                                            $dailyOt = round(max(0, $overtimeMinutes / 60.0), 2);
                                        }
                                    }
                                }
                            }

                            if ($dailyOt > 0) {
                                $isHoliday = in_array($md, $publicHolidays);
                                $isWeekend = ($dayOfWeek === 0 || $dayOfWeek === 6);
                                if ($isHoliday) {
                                    $overtimeHoursHoliday += $dailyOt;
                                } elseif ($isWeekend) {
                                    $overtimeHoursWeekend += $dailyOt;
                                } else {
                                    $overtimeHoursNormal += $dailyOt;
                                }
                                $totalOvertimeHours += $dailyOt;
                            }

                            if ($isLate || $t->status === 'LATE') {
                                $late += $dayWeight;
                            } else {
                                $present += $dayWeight;
                            }
                        }
                    } else {
                        // Missing check-in completely on working day
                        if ($dayLeave) {
                            $absent += 0.5;
                            $unapprovedAbsentDays += 0.5;
                        } else {
                            $absent += 1.0;
                            $unapprovedAbsentDays += 1.0;
                        }
                    }
                } else {
                    // Non-working day (Weekend or Holiday)
                    if ($t && $t->status !== 'ABSENT') {
                        $dailyOt = 0.0;
                        if ($t->check_in && $t->check_out) {
                            $tz = $t->timezone ?: 'Asia/Ho_Chi_Minh';
                            $checkInCarbon = \Carbon\Carbon::parse($t->check_in)->setTimezone($tz);
                            $checkOutCarbon = \Carbon\Carbon::parse($t->check_out)->setTimezone($tz);
                            $overtimeMinutes = $checkInCarbon->diffInMinutes($checkOutCarbon, false);
                            if ($overtimeMinutes > 0) {
                                if ($overtimeMinutes > 240) {
                                    $overtimeMinutes -= 60; // Lunch break
                                    if ($overtimeMinutes < 0) $overtimeMinutes = 0;
                                }
                                $dailyOt = round(max(0, $overtimeMinutes / 60.0), 2);
                            }
                        }

                        if ($dailyOt > 0) {
                            $isHoliday = in_array($md, $publicHolidays);
                            if ($isHoliday) {
                                $overtimeHoursHoliday += $dailyOt;
                            } else {
                                $overtimeHoursWeekend += $dailyOt;
                            }
                            $totalOvertimeHours += $dailyOt;
                        }
                    }
                }

                $dailyTimesheetsMap[] = [
                    'date' => $dateStr,
                    'check_in' => $t ? $t->check_in : null,
                    'check_out' => $t ? $t->check_out : null,
                    'status' => $t ? $t->status : ($isWorkingDay ? 'ABSENT' : 'PRESENT'),
                    'expected_start' => $expectedStart,
                    'expected_end' => $expectedEnd,
                    'leave_session' => $dayLeave ? $dayLeave->leave_session : null,
                    'shift_name' => $shiftName,
                    'allow_overtime' => $allowOvertime,
                    'max_overtime_hours' => $maxOvertimeHours,
                ];
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
                'overtime_hours_normal' => round($overtimeHoursNormal, 2),
                'overtime_hours_weekend' => round($overtimeHoursWeekend, 2),
                'overtime_hours_holiday' => round($overtimeHoursHoliday, 2),
                'timesheets' => $dailyTimesheetsMap,
            ];
        });

        $paginatedEmployees->setCollection($mappedData);
        return $paginatedEmployees;
    }
}
