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
     */
    public function getActiveConfigForDate(string $date)
    {
        $config = \App\Models\WorkingHourConfig::whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if ($config) {
            return $config;
        }

        $defaultConfig = \App\Models\WorkingHourConfig::where('is_default', true)->first();

        if ($defaultConfig) {
            return $defaultConfig;
        }

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

        // Determine active expected check-in time
        $activeConfig = $this->getActiveConfigForDate($date);
        $expectedCheckIn = $activeConfig->start_time;

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
    public function getStatistics(string $yearMonth)
    {
        return $this->timesheetRepository->getMonthlyStatistics($yearMonth);
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
            ->orderBy('start_date', 'asc')
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
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'is_default' => !empty($data['is_default']),
            'saturday_mode' => isset($data['saturday_mode']) ? (int)$data['saturday_mode'] : 0,
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
}
