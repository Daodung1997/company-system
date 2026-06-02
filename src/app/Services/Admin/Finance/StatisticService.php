<?php

namespace App\Services\Admin\Finance;

use App\Repositories\Payment\PaymentRepository;
use App\Repositories\Wallet\WithdrawalRepository;
use App\Services\AbstractService;
use Illuminate\Http\Request;

class StatisticService extends AbstractService
{
    public function __construct(
        protected PaymentRepository $paymentRepository,
        protected WithdrawalRepository $withdrawalRepository
    ) {}

    /**
     * Profit statistics
     */
    public function profit(Request $request)
    {
        $filters = $request->input('filters', []);
        $dateFrom = $filters['start_date'] ?? $request->query('date_from');
        $dateTo = $filters['end_date'] ?? $request->query('date_to');
        $serviceId = $filters['service_id'] ?? $request->query('service_id');
        $groupBy = $filters['group_by'] ?? $request->query('group_by', 'day');
        $limit = $filters['limit'] ?? 10;

        $statistics = $this->paymentRepository->getProfitStatistics($dateFrom, $dateTo, $serviceId, $groupBy);
        $topServices = $this->paymentRepository->getTopServices($dateFrom, $dateTo, $limit);

        $statistics['top_services'] = $topServices;

        return $statistics;
    }

    /**
     * Cash flow statistics
     */
    public function cashFlow(Request $request)
    {
        $filters = $request->input('filters', []);
        $dateFrom = $filters['start_date'] ?? $request->query('date_from');
        $dateTo = $filters['end_date'] ?? $request->query('date_to');
        $groupBy = $filters['group_by'] ?? $request->query('group_by', 'day');
        $paymentMethod = $filters['payment_method'] ?? null;

        $paymentFlow = $this->paymentRepository->getCashFlowStatistics($dateFrom, $dateTo, $groupBy, $paymentMethod);
        $withdrawalFlow = $this->withdrawalRepository->getWithdrawalStatistics($dateFrom, $dateTo, $groupBy);

        // Merge chart data
        $chart = $this->mergeCashFlowChart($paymentFlow, $withdrawalFlow);

        // Summary
        $summary = [
            'payment_inflow' => $paymentFlow['inflow']->sum('payment_inflow'),
            'refund_outflow' => $paymentFlow['refund_outflow']->sum('refund_outflow'),
            'withdrawal_outflow' => $withdrawalFlow->sum('total_amount'),
        ];
        $summary['net_cash_movement'] = $summary['payment_inflow'] - $summary['refund_outflow'] - $summary['withdrawal_outflow'];

        return [
            'summary' => $summary,
            'chart' => $chart,
        ];
    }

    /**
     * Service revenue statistics
     */
    public function serviceRevenue(Request $request)
    {
        $filters = $request->input('filters', []);
        $dateFrom = $filters['start_date'] ?? $request->query('date_from');
        $dateTo = $filters['end_date'] ?? $request->query('date_to');
        $parentServiceId = $filters['parent_service_id'] ?? null;
        $limit = $filters['limit'] ?? 10;

        $services = $this->paymentRepository->getServiceRevenueStatistics($dateFrom, $dateTo, $parentServiceId, $limit);

        $totalRevenue = $services->sum('total_revenue');
        $summary = [
            'total_revenue' => $totalRevenue,
            'total_platform_fees' => $services->sum('total_platform_fees'),
            'total_worker_earnings' => $services->sum('total_worker_earnings'),
            'total_refunds' => $services->sum('total_refunds'),
            'net_profit' => $services->sum('total_platform_fees') - $services->sum('total_refunds'),
            'service_count' => $services->count(),
        ];

        $services = $services->map(function ($item) use ($totalRevenue) {
            $item->revenue_share_percent = $totalRevenue > 0 ? round(($item->total_revenue / $totalRevenue) * 100, 2) : 0;
            $item->net_profit = $item->total_platform_fees - $item->total_refunds;

            return $item;
        });

        return [
            'summary' => $summary,
            'services' => $services,
        ];
    }

    /**
     * Merge payment and withdrawal chart data
     */
    protected function mergeCashFlowChart($paymentFlow, $withdrawalFlow)
    {
        $periods = collect([])
            ->merge($paymentFlow['inflow']->pluck('period'))
            ->merge($paymentFlow['refund_outflow']->pluck('period'))
            ->merge($withdrawalFlow->pluck('period'))
            ->unique()
            ->sort()
            ->values();

        $inflowMap = $paymentFlow['inflow']->pluck('payment_inflow', 'period');
        $refundMap = $paymentFlow['refund_outflow']->pluck('refund_outflow', 'period');
        $withdrawalMap = $withdrawalFlow->pluck('total_amount', 'period');

        return $periods->map(function ($period) use ($inflowMap, $refundMap, $withdrawalMap) {
            $in = (float) ($inflowMap[$period] ?? 0);
            $refund = (float) ($refundMap[$period] ?? 0);
            $out = (float) ($withdrawalMap[$period] ?? 0);

            return [
                'period' => $period,
                'payment_inflow' => $in,
                'refund_outflow' => $refund,
                'withdrawal_outflow' => $out,
                'net_cash_movement' => $in - $refund - $out,
            ];
        });
    }
}
