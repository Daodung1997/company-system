<?php

namespace App\Repositories\Payment;

use App\Repositories\Contracts\RepositoryInterface;

interface PaymentRepository extends RepositoryInterface
{
    /**
     * Get profit statistics
     *
     * @param  string|null  $dateFrom
     * @param  string|null  $dateTo
     * @param  int|null  $serviceId
     * @param  string  $groupBy
     * @return array
     */
    public function getProfitStatistics($dateFrom = null, $dateTo = null, $serviceId = null, $groupBy = 'day');

    /**
     * Get top services by profit
     *
     * @param  string|null  $dateFrom
     * @param  string|null  $dateTo
     * @param  int  $limit
     * @return \Illuminate\Support\Collection
     */
    public function getTopServices($dateFrom = null, $dateTo = null, $limit = 5);

    /**
     * Get cash flow statistics
     */
    public function getCashFlowStatistics($dateFrom = null, $dateTo = null, $groupBy = 'day', $paymentMethod = null);

    /**
     * Get service revenue statistics
     */
    public function getServiceRevenueStatistics($dateFrom = null, $dateTo = null, $parentServiceId = null, $limit = 10);
}
