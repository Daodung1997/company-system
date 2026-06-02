<?php

namespace App\Repositories\Employee;

use App\Models\Employee;
use App\Repositories\Repository;

class EmployeeRepository extends Repository
{
    public function __construct(Employee $model)
    {
        parent::__construct($model);
    }

    /**
     * Find employee by email or phone.
     */
    public function findByEmailOrPhone(string $username): ?Employee
    {
        return $this->model
            ->where('email', $username)
            ->orWhere('phone', $username)
            ->first();
    }
}
