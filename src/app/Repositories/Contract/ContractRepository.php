<?php

namespace App\Repositories\Contract;

use App\Models\Contract;
use App\Repositories\Repository;

class ContractRepository extends Repository
{
    public function __construct(Contract $model)
    {
        parent::__construct($model);
    }

    /**
     * Get contracts for a company with filters.
     */
    public function getContracts(array $filters)
    {
        $query = $this->model->with(['employee', 'documents']);

        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('contract_code', 'like', "%{$q}%")
                    ->orWhereHas('employee', function ($empSub) use ($q) {
                        $empSub->where('full_name', 'like', "%{$q}%")
                            ->orWhere('code', 'like', "%{$q}%");
                    });
            });
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        return $query->orderBy('start_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Find an active labor contract for an employee.
     */
    public function findActiveLaborContract(int $employeeId): ?Contract
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->where('type', 'LABOR')
            ->where('status', 'ACTIVE')
            ->first();
    }

    /**
     * Check if employee has an active overlapping labor contract.
     */
    public function hasOverlappingActiveContract(int $employeeId, string $startDate, ?string $endDate, ?int $excludeId = null): bool
    {
        $query = $this->model
            ->where('employee_id', $employeeId)
            ->where('type', 'LABOR')
            ->where('status', 'ACTIVE');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $query->where(function ($sub) use ($startDate, $endDate) {
            $sub->where(function ($q) use ($startDate, $endDate) {
                if ($endDate) {
                    $q->where('start_date', '<=', $endDate)
                      ->where(function ($inner) use ($startDate) {
                          $inner->whereNull('end_date')
                                ->orWhere('end_date', '>=', $startDate);
                      });
                } else {
                    $q->where(function ($inner) use ($startDate) {
                        $inner->whereNull('end_date')
                              ->orWhere('end_date', '>=', $startDate);
                    });
                }
            });
        });

        return $query->exists();
    }
}
