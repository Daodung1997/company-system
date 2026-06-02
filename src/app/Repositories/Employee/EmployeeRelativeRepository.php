<?php

namespace App\Repositories\Employee;

use App\Models\EmployeeRelative;
use App\Repositories\Repository;

class EmployeeRelativeRepository extends Repository
{
    public function __construct(EmployeeRelative $model)
    {
        parent::__construct($model);
    }

    /**
     * Get all relatives for a specific employee.
     */
    public function getByEmployeeId(int $employeeId)
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->orderBy('relationship')
            ->orderBy('full_name')
            ->get();
    }

    /**
     * Get emergency contacts for a specific employee.
     */
    public function getEmergencyContacts(int $employeeId)
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->where('is_emergency_contact', true)
            ->get();
    }

    /**
     * Count dependents for a specific employee (for tax calculation).
     */
    public function countDependents(int $employeeId): int
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->where('is_dependent', true)
            ->count();
    }
}
