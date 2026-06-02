<?php

namespace App\Repositories\Payment;

use App\Constants\Master\Models\Payment\PaymentColumn;
use App\Constants\Master\Models\Payment\PaymentStatusConst;
use App\Models\Job;
use App\Models\Payment;
use App\Models\ServiceCategory;
use App\Repositories\Repository;
use Illuminate\Support\Facades\DB;

class PaymentRepositoryEloquent extends Repository implements PaymentRepository
{
    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function getProfitStatistics($dateFrom = null, $dateTo = null, $serviceId = null, $groupBy = 'day')
    {
        $query = $this->model->newQuery()
            ->where(PaymentColumn::STATUS, PaymentStatusConst::PAID);

        if ($dateFrom) {
            $query->whereDate(PaymentColumn::PAID_AT, '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate(PaymentColumn::PAID_AT, '<=', $dateTo);
        }
        if ($serviceId) {
            $query->whereHas('job', function ($q) use ($serviceId) {
                $q->where('service_id', $serviceId);
            });
        }

        $summary = (clone $query)->selectRaw(sprintf(
            'SUM(%s) as total_revenue, SUM(%s) as total_platform_fees, SUM(%s) as total_worker_earnings',
            PaymentColumn::AMOUNT,
            PaymentColumn::PLATFORM_FEE,
            PaymentColumn::WORKER_EARNING
        ))->first();

        $totalRefunds = $this->model->newQuery()
            ->where(PaymentColumn::STATUS, PaymentStatusConst::REFUNDED)
            ->when($dateFrom, fn ($q) => $q->whereDate(PaymentColumn::REFUNDED_AT, '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate(PaymentColumn::REFUNDED_AT, '<=', $dateTo))
            ->when($serviceId, fn ($q) => $q->whereHas('job', fn ($jq) => $jq->where('service_id', $serviceId)))
            ->sum(PaymentColumn::REFUNDED_AMOUNT);

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $strftime = match ($groupBy) {
                'month' => '%Y-%m',
                'week' => '%Y-%W', // Approximation
                default => '%Y-%m-%d',
            };
            $dateSelect = "strftime('$strftime', ".PaymentColumn::PAID_AT.')';
        } else {
            $dateFormat = match ($groupBy) {
                'month' => '%Y-%m',
                'week' => '%x-%v',
                default => '%Y-%m-%d',
            };
            $dateSelect = 'DATE_FORMAT('.PaymentColumn::PAID_AT.", '$dateFormat')";
        }

        $chart = $query->selectRaw(sprintf(
            '%s as date, SUM(%s) as revenue, SUM(%s) as profit, SUM(%s) as earnings',
            $dateSelect,
            PaymentColumn::AMOUNT,
            PaymentColumn::PLATFORM_FEE,
            PaymentColumn::WORKER_EARNING
        ))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'summary' => [
                'total_revenue' => (float) ($summary->total_revenue ?? 0),
                'total_platform_fees' => (float) ($summary->total_platform_fees ?? 0),
                'total_worker_earnings' => (float) ($summary->total_worker_earnings ?? 0),
                'total_refunds' => (float) ($totalRefunds ?? 0),
                'total_profit' => (float) (($summary->total_platform_fees ?? 0) - ($totalRefunds ?? 0)),
                'net_profit' => (float) (($summary->total_platform_fees ?? 0) - ($totalRefunds ?? 0)),
            ],
            'chart' => $chart,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getTopServices($dateFrom = null, $dateTo = null, $limit = 5)
    {
        $query = $this->model->newQuery()
            ->join(Job::TABLE_NAME, Payment::TABLE_NAME.'.'.PaymentColumn::JOB_ID, '=', Job::TABLE_NAME.'.id')
            ->join(ServiceCategory::TABLE_NAME, Job::TABLE_NAME.'.service_id', '=', ServiceCategory::TABLE_NAME.'.id')
            ->where(Payment::TABLE_NAME.'.'.PaymentColumn::STATUS, PaymentStatusConst::PAID);

        if ($dateFrom) {
            $query->whereDate(Payment::TABLE_NAME.'.'.PaymentColumn::PAID_AT, '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate(Payment::TABLE_NAME.'.'.PaymentColumn::PAID_AT, '<=', $dateTo);
        }

        return $query->selectRaw(sprintf(
            '%s.id as service_id, %s.name as service_name, SUM(%s.%s) as total_profit, SUM(%s.%s) as total_revenue, SUM(%s.%s) as total_platform_fees, SUM(%s.%s) as total_worker_earnings, COUNT(%s.id) as total_jobs',
            ServiceCategory::TABLE_NAME,
            ServiceCategory::TABLE_NAME,
            Payment::TABLE_NAME,
            PaymentColumn::PLATFORM_FEE,
            Payment::TABLE_NAME,
            PaymentColumn::AMOUNT,
            Payment::TABLE_NAME,
            PaymentColumn::PLATFORM_FEE,
            Payment::TABLE_NAME,
            PaymentColumn::WORKER_EARNING,
            Job::TABLE_NAME
        ))
            ->groupBy(ServiceCategory::TABLE_NAME.'.id', ServiceCategory::TABLE_NAME.'.name')
            ->orderByDesc('total_profit')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getCashFlowStatistics($dateFrom = null, $dateTo = null, $groupBy = 'day', $paymentMethod = null)
    {
        $query = $this->model->newQuery();

        if ($dateFrom) {
            $query->whereDate(PaymentColumn::PAID_AT, '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate(PaymentColumn::PAID_AT, '<=', $dateTo);
        }
        if ($paymentMethod) {
            $query->where(PaymentColumn::PAYMENT_METHOD, $paymentMethod);
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $strftime = match ($groupBy) {
                'month' => '%Y-%m',
                'week' => '%Y-%W',
                default => '%Y-%m-%d',
            };
            $dateSelect = "strftime('$strftime', ".PaymentColumn::PAID_AT.')';
        } else {
            $dateFormat = match ($groupBy) {
                'month' => '%Y-%m',
                'week' => '%x-%v',
                default => '%Y-%m-%d',
            };
            $dateSelect = 'DATE_FORMAT('.PaymentColumn::PAID_AT.", '$dateFormat')";
        }

        $inflow = (clone $query)->where(PaymentColumn::STATUS, PaymentStatusConst::PAID)
            ->selectRaw(sprintf(
                '%s as period, SUM(%s) as payment_inflow',
                $dateSelect,
                PaymentColumn::AMOUNT
            ))
            ->groupBy('period')
            ->get();

        $refundOutflow = (clone $query)->where(PaymentColumn::STATUS, PaymentStatusConst::REFUNDED)
            ->selectRaw(sprintf(
                '%s as period, SUM(%s) as refund_outflow',
                $dateSelect,
                PaymentColumn::REFUNDED_AMOUNT
            ))
            ->groupBy('period')
            ->get();

        return [
            'inflow' => $inflow,
            'refund_outflow' => $refundOutflow,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getServiceRevenueStatistics($dateFrom = null, $dateTo = null, $parentServiceId = null, $limit = 10)
    {
        $query = $this->model->newQuery()
            ->join(Job::TABLE_NAME, Payment::TABLE_NAME.'.'.PaymentColumn::JOB_ID, '=', Job::TABLE_NAME.'.id')
            ->join(ServiceCategory::TABLE_NAME, Job::TABLE_NAME.'.service_id', '=', ServiceCategory::TABLE_NAME.'.id')
            ->leftJoin(ServiceCategory::TABLE_NAME.' as parent', ServiceCategory::TABLE_NAME.'.parent_id', '=', 'parent.id')
            ->where(Payment::TABLE_NAME.'.'.PaymentColumn::STATUS, PaymentStatusConst::PAID);

        if ($dateFrom) {
            $query->whereDate(Payment::TABLE_NAME.'.'.PaymentColumn::PAID_AT, '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate(Payment::TABLE_NAME.'.'.PaymentColumn::PAID_AT, '<=', $dateTo);
        }
        if ($parentServiceId) {
            $query->where(function ($q) use ($parentServiceId) {
                $q->where(ServiceCategory::TABLE_NAME.'.parent_id', $parentServiceId)
                    ->orWhere(ServiceCategory::TABLE_NAME.'.id', $parentServiceId);
            });
        }

        return $query->selectRaw(sprintf(
            '%s.id as service_id, %s.name as service_name, parent.id as parent_service_id, parent.name as parent_service_name, COUNT(%s.id) as total_jobs, SUM(%s.%s) as total_revenue, SUM(%s.%s) as total_platform_fees, SUM(%s.%s) as total_worker_earnings, SUM(%s.%s) as total_refunds',
            ServiceCategory::TABLE_NAME,
            ServiceCategory::TABLE_NAME,
            Job::TABLE_NAME,
            Payment::TABLE_NAME,
            PaymentColumn::AMOUNT,
            Payment::TABLE_NAME,
            PaymentColumn::PLATFORM_FEE,
            Payment::TABLE_NAME,
            PaymentColumn::WORKER_EARNING,
            Payment::TABLE_NAME,
            PaymentColumn::REFUNDED_AMOUNT
        ))
            ->groupBy(ServiceCategory::TABLE_NAME.'.id', ServiceCategory::TABLE_NAME.'.name', 'parent.id', 'parent.name')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();
    }
}
