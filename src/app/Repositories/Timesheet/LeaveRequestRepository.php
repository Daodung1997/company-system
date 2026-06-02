<?php

namespace App\Repositories\Timesheet;

use App\Models\LeaveRequest;
use App\Repositories\Repository;

class LeaveRequestRepository extends Repository
{
    public function __construct(LeaveRequest $model)
    {
        parent::__construct($model);
    }

    /**
     * Get leave requests for an employee.
     */
    public function getByEmployeeId(int $employeeId)
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->orderBy('start_date', 'desc')
            ->get();
    }

    /**
     * Check if employee has overlapping leave request.
     */
    public function hasOverlappingLeave(int $employeeId, string $startDate, string $endDate, ?int $excludeId = null): bool
    {
        $query = $this->model
            ->where('employee_id', $employeeId)
            ->where('status', '!=', 'REJECTED')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where(function ($sub) use ($startDate, $endDate) {
                    $sub->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $startDate);
                })->orWhere(function ($sub) use ($startDate, $endDate) {
                    $sub->where('start_date', '<=', $endDate)
                        ->where('end_date', '>=', $endDate);
                })->orWhere(function ($sub) use ($startDate, $endDate) {
                    $sub->where('start_date', '>=', $startDate)
                        ->where('end_date', '<=', $endDate);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
