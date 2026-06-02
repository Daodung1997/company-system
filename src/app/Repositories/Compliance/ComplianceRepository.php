<?php

namespace App\Repositories\Compliance;

use App\Models\ComplianceIssue;
use App\Repositories\Repository;

class ComplianceRepository extends Repository
{
    public function __construct(ComplianceIssue $model)
    {
        parent::__construct($model);
    }

    /**
     * Get compliance issues for a company with filters.
     */
    public function getIssues(array $filters)
    {
        $query = $this->model->with(['employee', 'contract', 'transaction']);

        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('description', 'like', "%{$q}%")
                    ->orWhere('issue_type', 'like', "%{$q}%")
                    ->orWhereHas('employee', function ($empSub) use ($q) {
                        $empSub->where('full_name', 'like', "%{$q}%")
                            ->orWhere('code', 'like', "%{$q}%");
                    })
                    ->orWhereHas('contract', function ($conSub) use ($q) {
                        $conSub->where('contract_code', 'like', "%{$q}%");
                    })
                    ->orWhereHas('transaction', function ($txnSub) use ($q) {
                        $txnSub->where('code', 'like', "%{$q}%")
                            ->orWhere('category', 'like', "%{$q}%");
                    });
            });
        }

        if (!empty($filters['issue_type'])) {
            $query->where('issue_type', $filters['issue_type']);
        }

        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            // Default to OPEN if no status is specified
            $query->where('status', 'OPEN');
        }

        return $query->orderBy('severity', 'asc') // We will sort CRITICAL -> WARNING -> INFO if mapped, or just by created_at
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
