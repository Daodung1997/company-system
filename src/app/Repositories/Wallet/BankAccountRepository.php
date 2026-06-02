<?php

namespace App\Repositories\Wallet;

use App\Models\BankAccount;
use App\Repositories\Repository;

class BankAccountRepository extends Repository
{
    public function __construct(BankAccount $model)
    {
        parent::__construct($model);
    }

    /**
     * Set a bank account as default and unset others
     */
    public function setAsDefault(int $bankAccountId, int $userId): void
    {
        // Unset all defaults for this user
        $this->model
            ->where('user_id', $userId)
            ->update(['is_default' => false]);

        // Set the specified account as default
        $this->model
            ->where('id', $bankAccountId)
            ->where('user_id', $userId)
            ->update(['is_default' => true]);
    }

    /**
     * Count bank accounts for a user
     */
    public function countByUser(int $userId): int
    {
        return $this->model
            ->where('user_id', $userId)
            ->count();
    }

    /**
     * Get all bank accounts for a user
     */
    public function getAllByUser(int $userId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->get();
    }
}
