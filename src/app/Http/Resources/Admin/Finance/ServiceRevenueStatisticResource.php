<?php

namespace App\Http\Resources\Admin\Finance;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRevenueStatisticResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'summary' => [
                'total_revenue' => (float) $this->resource['summary']['total_revenue'],
                'total_platform_fees' => (float) $this->resource['summary']['total_platform_fees'],
                'total_worker_earnings' => (float) $this->resource['summary']['total_worker_earnings'],
                'total_refunds' => (float) $this->resource['summary']['total_refunds'],
                'net_profit' => (float) $this->resource['summary']['net_profit'],
                'service_count' => (int) $this->resource['summary']['service_count'],
            ],
            'services' => collect($this->resource['services'])->map(function ($item) {
                return [
                    'service_id' => $item->service_id,
                    'service_name' => $item->service_name,
                    'parent_service_id' => $item->parent_service_id,
                    'parent_service_name' => $item->parent_service_name,
                    'total_jobs' => (int) $item->total_jobs,
                    'total_revenue' => (float) $item->total_revenue,
                    'total_platform_fees' => (float) $item->total_platform_fees,
                    'total_worker_earnings' => (float) $item->total_worker_earnings,
                    'total_refunds' => (float) $item->total_refunds,
                    'net_profit' => (float) $item->net_profit,
                    'revenue_share_percent' => (float) $item->revenue_share_percent,
                ];
            }),
        ];
    }
}
