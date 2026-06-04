<?php

namespace App\Services\Timesheet;

use App\Constants\Commons\ExceptionCode;
use App\Exceptions\BusinessException;
use App\Repositories\Timesheet\TimesheetRepository;
use App\Services\AbstractService;
use Carbon\Carbon;

class TimesheetService extends AbstractService
{
    public function __construct(
        protected TimesheetRepository $timesheetRepository
    ) {}

    /**
     * Get monthly timesheets for an employee.
     */
    public function getMonthly(int $employeeId, string $yearMonth)
    {
        return $this->timesheetRepository->getMonthlyTimesheets($employeeId, $yearMonth);
    }

    /**
     * Get the active working hour config for a specific date.
     * Since working_hour_configs does not have date-range columns,
     * we simply return the default config.
     */
    public function getActiveConfigForDate(string $date)
    {
        $defaultConfig = \App\Models\WorkingHourConfig::where('is_default', true)->first();

        if ($defaultConfig) {
            return $defaultConfig;
        }

        // Fallback hardcoded config if none exists in the database
        return (object)[
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ];
    }

    /**
     * Check in action.
     */
    public function checkIn(int $employeeId, array $data)
    {
        $timezone = $data['timezone'] ?? 'Asia/Ho_Chi_Minh';
        $now = Carbon::now($timezone);
        $date = $now->toDateString();

        // Check if timesheet already exists for today
        $existing = $this->timesheetRepository->findByEmployeeAndDate($employeeId, $date);
        if ($existing && $existing->check_in) {
            throw new BusinessException(
                ExceptionCode::TIMESHEET_ALREADY_EXISTS,
                'Bạn đã check-in ngày hôm nay rồi.',
                400
            );
        }

        // Determine active expected check-in time (check shift first, then fallback to active config)
        $employeeShift = \App\Models\EmployeeShift::with('workingHourConfig')
            ->where('employee_id', $employeeId)
            ->whereDate('date', $date)
            ->first();

        if ($employeeShift && $employeeShift->workingHourConfig) {
            $expectedCheckIn = $employeeShift->workingHourConfig->start_time;
        } else {
            $activeConfig = $this->getActiveConfigForDate($date);
            $expectedCheckIn = $activeConfig->start_time;
        }

        // Determine status (LATE if check-in is after expected check-in threshold)
        $status = 'PRESENT';
        $limitTime = Carbon::createFromFormat('Y-m-d H:i:s', "$date $expectedCheckIn", $timezone);
        if ($now->greaterThan($limitTime)) {
            $status = 'LATE';
        }

        $this->beginTransaction();
        try {
            if ($existing) {
                $this->timesheetRepository->update($existing->id, [
                    'check_in' => $now->toDateTimeString(),
                    'timezone' => $timezone,
                    'status' => $status,
                    'note' => $data['note'] ?? null,
                ]);
                $timesheet = $existing->refresh();
            } else {
                $timesheet = $this->timesheetRepository->create([
                    'employee_id' => $employeeId,
                    'date' => $date,
                    'check_in' => $now->toDateTimeString(),
                    'timezone' => $timezone,
                    'status' => $status,
                    'note' => $data['note'] ?? null,
                ]);
            }

            $this->commitTransaction();
            return $timesheet;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Check out action.
     */
    public function checkOut(int $employeeId, array $data)
    {
        $timezone = $data['timezone'] ?? 'Asia/Ho_Chi_Minh';
        $now = Carbon::now($timezone);
        $date = $now->toDateString();

        $existing = $this->timesheetRepository->findByEmployeeAndDate($employeeId, $date);

        $this->beginTransaction();
        try {
            if ($existing) {
                $this->timesheetRepository->update($existing->id, [
                    'check_out' => $now->toDateTimeString(),
                    'note' => $data['note'] ?? $existing->note,
                ]);
                $timesheet = $existing->refresh();
            } else {
                // If checking out without check-in, create a record with check_out only
                $timesheet = $this->timesheetRepository->create([
                    'employee_id' => $employeeId,
                    'date' => $date,
                    'check_out' => $now->toDateTimeString(),
                    'timezone' => $timezone,
                    'status' => 'PRESENT',
                    'note' => $data['note'] ?? null,
                ]);
            }

            $this->commitTransaction();
            return $timesheet;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Get timesheets list for admin/manager.
     */
    public function getForAdmin(array $filters)
    {
        return $this->timesheetRepository->getTimesheetsForAdmin($filters);
    }

    /**
     * Get statistics for all employees in a given month.
     */
    public function getStatistics(string $yearMonth, int $page = 1, int $perPage = 15, ?string $search = null)
    {
        return $this->timesheetRepository->getMonthlyStatistics($yearMonth, $page, $perPage, $search);
    }

    /**
     * Store or update manual timesheet record (Admin/Manager).
     */
    public function storeManual(array $data)
    {
        $employeeId = $data['employee_id'];
        $date = $data['date'];
        
        $existing = $this->timesheetRepository->findByEmployeeAndDate($employeeId, $date);
        
        $payload = [
            'employee_id' => $employeeId,
            'date' => $date,
            'check_in' => $data['check_in'] ?? null,
            'check_out' => $data['check_out'] ?? null,
            'status' => $data['status'] ?? 'PRESENT',
            'note' => $data['note'] ?? null,
            'timezone' => $data['timezone'] ?? 'Asia/Ho_Chi_Minh',
        ];
        
        $this->beginTransaction();
        try {
            if ($existing) {
                // Remove nulls to avoid clearing existing fields if not supplied
                $updateData = array_filter($payload, function ($value) {
                    return !is_null($value);
                });
                $this->timesheetRepository->update($existing->id, $updateData);
                $timesheet = $existing->refresh();
            } else {
                $timesheet = $this->timesheetRepository->create($payload);
            }
            
            $this->commitTransaction();
            return $timesheet;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Get all working hour configs.
     */
    public function listWorkingHourConfigs()
    {
        return \App\Models\WorkingHourConfig::orderBy('is_default', 'desc')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Store or update working hour config.
     */
    public function storeWorkingHourConfig(array $data)
    {
        $id = $data['id'] ?? null;

        $payload = [
            'name' => $data['name'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'is_default' => !empty($data['is_default']),
            'allow_overtime' => !empty($data['allow_overtime']) ? 1 : 0,
            'max_overtime_hours' => isset($data['max_overtime_hours']) ? (double)$data['max_overtime_hours'] : null,
        ];

        $this->beginTransaction();
        try {
            if (!empty($payload['is_default'])) {
                // Reset other default flags
                \App\Models\WorkingHourConfig::where('is_default', true)->update(['is_default' => false]);
            }

            if ($id) {
                $config = \App\Models\WorkingHourConfig::findOrFail($id);
                $config->update($payload);
            } else {
                $config = \App\Models\WorkingHourConfig::create($payload);
            }

            $this->commitTransaction();
            return $config;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Delete working hour config.
     */
    public function deleteWorkingHourConfig(int $id)
    {
        $this->beginTransaction();
        try {
            $config = \App\Models\WorkingHourConfig::findOrFail($id);
            if ($config->is_default) {
                throw new BusinessException(
                    ExceptionCode::BAD_REQUEST,
                    'Không thể xóa cấu hình mặc định của hệ thống.',
                    400
                );
            }
            $config->delete();
            $this->commitTransaction();
            return true;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }



    /**
     * List employee shift assignments.
     */
    public function listEmployeeShifts(array $filters)
    {
        $query = \App\Models\EmployeeShift::with(['employee', 'workingHourConfig']);

        if (!empty($filters['date'])) {
            $query->whereDate('date', $filters['date']);
        } elseif (!empty($filters['year_month'])) {
            $query->where('date', 'like', $filters['year_month'] . '%');
        }

        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    /**
     * Store bulk employee shift assignments.
     */
    public function storeEmployeeShift(array $data)
    {
        $employeeIds = (array)$data['employee_ids'];
        $dates = (array)$data['dates'];
        $workingHourConfigId = $data['working_hour_config_id'];

        $this->beginTransaction();
        try {
            $assignments = [];
            foreach ($employeeIds as $empId) {
                foreach ($dates as $date) {
                    $assignment = \App\Models\EmployeeShift::updateOrCreate(
                        ['employee_id' => $empId, 'date' => $date],
                        ['working_hour_config_id' => $workingHourConfigId]
                    );
                    $assignments[] = $assignment;
                }
            }
            $this->commitTransaction();
            return $assignments;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Delete employee shift assignment.
     */
    public function deleteEmployeeShift(int $id)
    {
        $this->beginTransaction();
        try {
            $assignment = \App\Models\EmployeeShift::findOrFail($id);
            $assignment->delete();
            $this->commitTransaction();
            return true;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * List employee shift calendar assignments with pagination, shifts and leave requests.
     */
    public function listEmployeeShiftsCalendar(array $params)
    {
        $yearMonth = $params['year_month'] ?? Carbon::now()->format('Y-m');
        $startDate = Carbon::parse($yearMonth . '-01')->toDateString();
        $endDate = Carbon::parse($yearMonth . '-01')->endOfMonth()->toDateString();

        $query = \App\Models\Employee::with([
            'department',
            'jobTitle',
            'employeeShifts' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate])->with('workingHourConfig');
            },
            'leaveRequests' => function ($q) use ($startDate, $endDate) {
                $q->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($query2) use ($startDate, $endDate) {
                            $query2->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                })->whereIn('status', ['PENDING', 'APPROVED']);
            }
        ]);

        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        if (!empty($params['department_id'])) {
            $query->where('department_id', $params['department_id']);
        }

        if (!empty($params['job_title_id'])) {
            $query->where('job_title_id', $params['job_title_id']);
        }

        $perPage = $params['per_page'] ?? 15;
        return $query->orderBy('code', 'asc')->paginate($perPage);
    }

    /**
     * Reset/Delete employee shift assignments for specific dates.
     */
    public function resetEmployeeShifts(array $data)
    {
        $employeeIds = (array)$data['employee_ids'];
        $dates = (array)$data['dates'];

        $this->beginTransaction();
        try {
            \App\Models\EmployeeShift::whereIn('employee_id', $employeeIds)
                ->whereIn('date', $dates)
                ->delete();
            $this->commitTransaction();
            return true;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Get payroll list for a month.
     */
    public function getPayroll(string $yearMonth, int $page = 1, int $perPage = 15, ?string $search = null)
    {
        // 1. Get statistics first
        $statsPaginator = $this->getStatistics($yearMonth, $page, $perPage, $search);

        // 2. Fetch existing payslips for these employees in this month
        $employeeIds = collect($statsPaginator->items())->pluck('employee_id')->toArray();
        $existingPayslips = \App\Models\Payslip::where('year_month', $yearMonth)
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->keyBy('employee_id');

        $mappedItems = collect($statsPaginator->items())->map(function ($stat) use ($existingPayslips, $yearMonth) {
            $employeeId = $stat['employee_id'];
            $employee = \App\Models\Employee::with(['activeContract', 'department', 'jobTitle'])->find($employeeId);
            
            // Get base salary from active contract or latest work history
            $baseSalary = 10000000;
            if ($employee) {
                if ($employee->activeContract) {
                    $baseSalary = (double) $employee->activeContract->value;
                } else {
                    $latestHistory = $employee->workHistories()->orderBy('start_date', 'desc')->first();
                    if ($latestHistory && $latestHistory->salary) {
                        $baseSalary = (double) $latestHistory->salary;
                    }
                }
            }

            $standardDays = (double) ($stat['max_working_days'] ?: 22);
            $actualDays = (double) $stat['total_working_days'];
            $otHours = (double) $stat['total_overtime_hours'];
            $lateHours = (double) $stat['total_late_hours'];
            $unexcusedDays = (double) $stat['unapproved_absent_days'];

            // Calculate standard rates
            $dailyRate = $standardDays > 0 ? ($baseSalary / $standardDays) : 0;
            $hourlyRate = $dailyRate / 8;

            // Compute default calculations
            // Attendance allowance (chuyên cần): 500,000 VND if perfect attendance
            $defaultAllowance = ($actualDays >= $standardDays && $unexcusedDays == 0 && $lateHours == 0) ? 500000 : 0;
            
            // Extract overtime hours breakdown from stats
            $otHoursNormal = (double) ($stat['overtime_hours_normal'] ?? 0.0);
            $otHoursWeekend = (double) ($stat['overtime_hours_weekend'] ?? 0.0);
            $otHoursHoliday = (double) ($stat['overtime_hours_holiday'] ?? 0.0);

            // Calculate overtime pay with correct multipliers
            // Normal OT multiplier: 1.5x
            $defaultOtSalaryNormal = $otHoursNormal * $hourlyRate * 1.5;
            // Weekend OT multiplier: 2.0x
            $defaultOtSalaryWeekend = $otHoursWeekend * $hourlyRate * 2.0;
            // Holiday OT multiplier: 3.0x
            $defaultOtSalaryHoliday = $otHoursHoliday * $hourlyRate * 3.0;

            $defaultOtSalary = $defaultOtSalaryNormal + $defaultOtSalaryWeekend + $defaultOtSalaryHoliday;
            
            // Deductions
            $defaultDeductionLate = $lateHours * $hourlyRate;
            $defaultDeductionLeave = $unexcusedDays * $dailyRate;

            // If a payslip exists in the database, we use its saved values (user overrides)
            if ($existingPayslips->has($employeeId)) {
                $payslip = $existingPayslips->get($employeeId);
                return [
                    'id' => $payslip->id,
                    'employee_id' => $employeeId,
                    'employee_code' => $stat['employee_code'],
                    'full_name' => $stat['full_name'],
                    'email' => $stat['email'],
                    'department_name' => $employee && $employee->department ? $employee->department->name : '',
                    'job_title_name' => $employee && $employee->jobTitle ? $employee->jobTitle->name : '',
                    'year_month' => $yearMonth,
                    'base_salary' => (double) $payslip->base_salary,
                    'standard_working_days' => (double) $payslip->standard_working_days,
                    'actual_working_days' => (double) $payslip->actual_working_days,
                    'overtime_hours' => (double) $payslip->overtime_hours,
                    'overtime_salary' => (double) $payslip->overtime_salary,
                    'overtime_hours_normal' => (double) ($payslip->overtime_hours_normal ?? 0),
                    'overtime_salary_normal' => (double) ($payslip->overtime_salary_normal ?? 0),
                    'overtime_hours_weekend' => (double) ($payslip->overtime_hours_weekend ?? 0),
                    'overtime_salary_weekend' => (double) ($payslip->overtime_salary_weekend ?? 0),
                    'overtime_hours_holiday' => (double) ($payslip->overtime_hours_holiday ?? 0),
                    'overtime_salary_holiday' => (double) ($payslip->overtime_salary_holiday ?? 0),
                    'allowance_attendance' => (double) $payslip->allowance_attendance,
                    'deduction_late' => (double) $payslip->deduction_late,
                    'deduction_leave' => (double) $payslip->deduction_leave,
                    'deduction_union' => (double) ($payslip->deduction_union ?? 50000.0),
                    'deduction_tax' => (double) ($payslip->deduction_tax ?? 0),
                    'advance_payment' => (double) $payslip->advance_payment,
                    'net_salary' => (double) $payslip->net_salary,
                    'status' => $payslip->status,
                    'note' => $payslip->note,
                    'dependents_count' => $employee ? $employee->dependents_count : 0,
                    'is_saved' => true,
                ];
            } else {
                // Calculate net salary and progressive income tax (PIT)
                $defaultUnionFee = 50000.0;
                $grossTaxableIncome = $baseSalary + $defaultOtSalary + $defaultAllowance - $defaultDeductionLate - $defaultDeductionLeave;
                $dependentsCount = $employee ? $employee->dependents_count : 0;
                $defaultTax = $this->calculateVietnamesePIT($grossTaxableIncome, $dependentsCount);
                $netSalary = $grossTaxableIncome - $defaultTax - $defaultUnionFee;

                return [
                    'id' => null,
                    'employee_id' => $employeeId,
                    'employee_code' => $stat['employee_code'],
                    'full_name' => $stat['full_name'],
                    'email' => $stat['email'],
                    'department_name' => $employee && $employee->department ? $employee->department->name : '',
                    'job_title_name' => $employee && $employee->jobTitle ? $employee->jobTitle->name : '',
                    'year_month' => $yearMonth,
                    'base_salary' => $baseSalary,
                    'standard_working_days' => $standardDays,
                    'actual_working_days' => $actualDays,
                    'overtime_hours' => $otHours,
                    'overtime_salary' => round($defaultOtSalary, 2),
                    'overtime_hours_normal' => $otHoursNormal,
                    'overtime_salary_normal' => round($defaultOtSalaryNormal, 2),
                    'overtime_hours_weekend' => $otHoursWeekend,
                    'overtime_salary_weekend' => round($defaultOtSalaryWeekend, 2),
                    'overtime_hours_holiday' => $otHoursHoliday,
                    'overtime_salary_holiday' => round($defaultOtSalaryHoliday, 2),
                    'allowance_attendance' => $defaultAllowance,
                    'deduction_late' => round($defaultDeductionLate, 2),
                    'deduction_leave' => round($defaultDeductionLeave, 2),
                    'deduction_union' => $defaultUnionFee,
                    'deduction_tax' => round($defaultTax, 2),
                    'advance_payment' => 0.0,
                    'net_salary' => round(max(0, $netSalary), 2),
                    'status' => 'PENDING',
                    'note' => '',
                    'dependents_count' => $dependentsCount,
                    'is_saved' => false,
                ];
            }
        });

        // Return mapped pagination object
        return [
            'data' => $mappedItems->all(),
            'meta' => [
                'current_page' => $statsPaginator->currentPage(),
                'last_page' => $statsPaginator->lastPage(),
                'per_page' => $statsPaginator->perPage(),
                'total' => $statsPaginator->total(),
            ]
        ];
    }

    /**
     * Save/Update a payroll record (payslip).
     */
    public function savePayroll(array $data)
    {
        $currentUser = auth('api')->user();
        $employeeId = $data['employee_id'];
        $yearMonth = $data['year_month'];

        $this->beginTransaction();
        try {
            $payslip = \App\Models\Payslip::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'year_month' => $yearMonth,
                ],
                [
                    'base_salary' => $data['base_salary'] ?? 0,
                    'standard_working_days' => $data['standard_working_days'] ?? 0,
                    'actual_working_days' => $data['actual_working_days'] ?? 0,
                    'overtime_hours' => $data['overtime_hours'] ?? 0,
                    'overtime_salary' => $data['overtime_salary'] ?? 0,
                    'overtime_hours_normal' => $data['overtime_hours_normal'] ?? 0,
                    'overtime_salary_normal' => $data['overtime_salary_normal'] ?? 0,
                    'overtime_hours_weekend' => $data['overtime_hours_weekend'] ?? 0,
                    'overtime_salary_weekend' => $data['overtime_salary_weekend'] ?? 0,
                    'overtime_hours_holiday' => $data['overtime_hours_holiday'] ?? 0,
                    'overtime_salary_holiday' => $data['overtime_salary_holiday'] ?? 0,
                    'allowance_attendance' => $data['allowance_attendance'] ?? 0,
                    'deduction_late' => $data['deduction_late'] ?? 0,
                    'deduction_leave' => $data['deduction_leave'] ?? 0,
                    'deduction_union' => $data['deduction_union'] ?? 0,
                    'deduction_tax' => $data['deduction_tax'] ?? 0,
                    'advance_payment' => $data['advance_payment'] ?? 0,
                    'net_salary' => $data['net_salary'] ?? 0,
                    'status' => $data['status'] ?? 'PENDING',
                    'note' => $data['note'] ?? null,
                    'updated_by' => $currentUser ? $currentUser->full_name : null,
                ]
            );

            if ($payslip->wasRecentlyCreated && $currentUser) {
                $payslip->update(['created_by' => $currentUser->full_name]);
            }

            $this->commitTransaction();
            return $payslip;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Calculate personal income tax (PIT) according to 2026 Vietnamese progressive tax brackets.
     */
    private function calculateVietnamesePIT(float $grossTaxableIncome, int $dependentsCount): float
    {
        // Giảm trừ bản thân (Self deduction): 11,000,000 VND
        $selfDeduction = 11000000;
        // Giảm trừ gia cảnh (Dependent deduction): 4,400,000 VND per dependent
        $dependentDeduction = $dependentsCount * 4400000;
        
        // Thu nhập tính thuế (Taxable income after deductions)
        $taxableIncome = max(0.0, $grossTaxableIncome - $selfDeduction - $dependentDeduction);
        
        if ($taxableIncome <= 0) {
            return 0.0;
        }
        
        // 2026 Vietnamese progressive tax brackets (Biểu thuế lũy tiến từng phần)
        if ($taxableIncome <= 5000000) {
            return $taxableIncome * 0.05;
        } elseif ($taxableIncome <= 10000000) {
            return $taxableIncome * 0.10 - 250000;
        } elseif ($taxableIncome <= 18000000) {
            return $taxableIncome * 0.15 - 750000;
        } elseif ($taxableIncome <= 32000000) {
            return $taxableIncome * 0.20 - 1650000;
        } elseif ($taxableIncome <= 52000000) {
            return $taxableIncome * 0.25 - 3250000;
        } elseif ($taxableIncome <= 80000000) {
            return $taxableIncome * 0.30 - 5850000;
        } else {
            return $taxableIncome * 0.35 - 9850000;
        }
    }
}
