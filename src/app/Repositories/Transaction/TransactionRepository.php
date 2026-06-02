<?php

namespace App\Repositories\Transaction;

use App\Models\Transaction;
use App\Repositories\Repository;

class TransactionRepository extends Repository
{
    public function __construct(Transaction $model)
    {
        parent::__construct($model);
    }

    /**
     * Get transactions with filters under a company.
     */
    public function getTransactions(array $filters)
    {
        $query = $this->model->with(['documents']);

        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('code', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('invoice_registration_number', 'like', "%{$q}%");
            });
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('transaction_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('transaction_date', '<=', $filters['end_date']);
        }

        return $query->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }
}
