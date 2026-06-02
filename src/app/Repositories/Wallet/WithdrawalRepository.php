<?php

namespace App\Repositories\Wallet;

use App\Models\Withdrawal;
use App\Repositories\Repository;
use Illuminate\Support\Facades\DB;

class WithdrawalRepository extends Repository
{
    public function __construct(Withdrawal $model)
    {
        parent::__construct($model);
    }

    /**
     * Check if worker has pending withdrawal
     */
    public function hasPendingWithdrawal(int $workerId): bool
    {
        return $this->model
            ->where('worker_id', $workerId)
            ->pending()
            ->exists();
    }

    /**
     * Get withdrawal statistics
     */
    public function getWithdrawalStatistics($dateFrom = null, $dateTo = null, $groupBy = 'day')
    {
        $query = $this->model->newQuery()
            ->completed();

        if ($dateFrom) {
            $query->whereDate('processed_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('processed_at', '<=', $dateTo);
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $strftime = match ($groupBy) {
                'month' => '%Y-%m',
                'week' => '%Y-%W',
                default => '%Y-%m-%d',
            };
            $dateSelect = "strftime('$strftime', processed_at)";
        } else {
            $dateFormat = match ($groupBy) {
                'month' => '%Y-%m',
                'week' => '%x-%v',
                default => '%Y-%m-%d',
            };
            $dateSelect = "DATE_FORMAT(processed_at, '$dateFormat')";
        }

        return $query->selectRaw("{$dateSelect} as period, SUM(amount) as total_amount")
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }
}
