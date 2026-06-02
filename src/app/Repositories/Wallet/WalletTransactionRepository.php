<?php

namespace App\Repositories\Wallet;

use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionStatusConst;
use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionTypeConst;
use App\Models\WalletTransaction;
use App\Repositories\Repository;

class WalletTransactionRepository extends Repository
{
    public function __construct(WalletTransaction $model)
    {
        parent::__construct($model);
    }

    /**
     * Calculate available balance for a worker.
     * available = SUM(earning released) - SUM(withdrawal pending/completed)
     */
    public function calculateAvailableBalance(int $workerId): float
    {
        $earnings = $this->model->newQuery()
            ->where('worker_id', $workerId)
            ->where('type', WalletTransactionTypeConst::EARNING)
            ->where('status', WalletTransactionStatusConst::RELEASED)
            ->sum('amount');

        $withdrawals = $this->model->newQuery()
            ->where('worker_id', $workerId)
            ->where('type', WalletTransactionTypeConst::WITHDRAWAL)
            ->whereIn('status', [
                WalletTransactionStatusConst::PENDING,
                WalletTransactionStatusConst::COMPLETED,
            ])
            ->sum('amount');

        $fees = $this->model->newQuery()
            ->where('worker_id', $workerId)
            ->where('type', WalletTransactionTypeConst::FEE)
            ->where('status', WalletTransactionStatusConst::RELEASED)
            ->sum('amount');

        return (float) ($earnings - $withdrawals - $fees);
    }

    /**
     * Calculate pending balance for a worker.
     */
    public function calculatePendingBalance(int $workerId): float
    {
        return (float) $this->model->newQuery()
            ->where('worker_id', $workerId)
            ->where('type', WalletTransactionTypeConst::EARNING)
            ->where('status', WalletTransactionStatusConst::PENDING)
            ->sum('amount');
    }

    /**
     * Get total earnings (all time).
     */
    public function getTotalEarnings(int $workerId): float
    {
        return (float) $this->model->newQuery()
            ->where('worker_id', $workerId)
            ->where('type', WalletTransactionTypeConst::EARNING)
            ->whereIn('status', [
                WalletTransactionStatusConst::PENDING,
                WalletTransactionStatusConst::RELEASED,
                WalletTransactionStatusConst::COMPLETED, // Include Cash earnings
            ])
            ->sum('amount');
    }

    /**
     * Get total withdrawn (all time).
     */
    public function getTotalWithdrawn(int $workerId): float
    {
        return (float) $this->model->newQuery()
            ->where('worker_id', $workerId)
            ->where('type', WalletTransactionTypeConst::WITHDRAWAL)
            ->where('status', WalletTransactionStatusConst::COMPLETED)
            ->sum('amount');
    }

    public function createPendingEarning(
        int $workerId,
        int $jobId,
        float|int $amount,
        string $description,
        ?\DateTimeInterface $releaseAt = null,
        ?string $actor = null
    ): WalletTransaction {
        return $this->updateOrCreate(
            [
                'worker_id' => $workerId,
                'job_id' => $jobId,
                'type' => WalletTransactionTypeConst::EARNING,
            ],
            [
                'amount' => $amount,
                'status' => WalletTransactionStatusConst::PENDING,
                'release_at' => $releaseAt,
                'description' => $description,
                'created_by' => $actor,
                'updated_by' => $actor,
            ]
        );
    }

    public function releaseEarning(
        int $workerId,
        int $jobId,
        float|int $amount,
        string $description,
        ?string $actor = null
    ): WalletTransaction {
        return $this->updateOrCreate(
            [
                'worker_id' => $workerId,
                'job_id' => $jobId,
                'type' => WalletTransactionTypeConst::EARNING,
            ],
            [
                'amount' => $amount,
                'status' => WalletTransactionStatusConst::RELEASED,
                'description' => $description,
                'created_by' => $actor,
                'updated_by' => $actor,
            ]
        );
    }

    public function recordCashEarning(
        int $workerId,
        int $jobId,
        float|int $amount,
        string $description,
        ?string $actor = null
    ): WalletTransaction {
        return $this->updateOrCreate(
            [
                'worker_id' => $workerId,
                'job_id' => $jobId,
                'type' => WalletTransactionTypeConst::EARNING,
            ],
            [
                'amount' => $amount,
                'status' => WalletTransactionStatusConst::COMPLETED,
                'description' => $description,
                'created_by' => $actor,
                'updated_by' => $actor,
            ]
        );
    }

    public function createPendingWithdrawal(
        int $workerId,
        int $withdrawalId,
        float|int $amount,
        string $description,
        ?string $actor = null
    ): WalletTransaction {
        return $this->create([
            'worker_id' => $workerId,
            'withdrawal_id' => $withdrawalId,
            'amount' => $amount,
            'type' => WalletTransactionTypeConst::WITHDRAWAL,
            'status' => WalletTransactionStatusConst::PENDING,
            'description' => $description,
            'job_id' => null,
            'created_by' => $actor,
            'updated_by' => $actor,
        ]);
    }

    public function completeWithdrawal(int $withdrawalId, ?string $actor = null): int
    {
        return $this->model->newQuery()
            ->where('withdrawal_id', $withdrawalId)
            ->where('type', WalletTransactionTypeConst::WITHDRAWAL)
            ->update([
                'status' => WalletTransactionStatusConst::COMPLETED,
                'updated_by' => $actor,
            ]);
    }

    public function failWithdrawal(int $withdrawalId, ?string $actor = null): int
    {
        return $this->model->newQuery()
            ->where('withdrawal_id', $withdrawalId)
            ->where('type', WalletTransactionTypeConst::WITHDRAWAL)
            ->update([
                'status' => WalletTransactionStatusConst::FAILED,
                'updated_by' => $actor,
            ]);
    }
}
