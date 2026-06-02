<?php

namespace App\Http\Resources\Admin\Finance;

use Illuminate\Http\Resources\Json\JsonResource;

class StatisticResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'summary' => [
                'total_revenue' => (float) $this->resource['summary']['total_revenue'],
                'total_platform_fees' => (float) ($this->resource['summary']['total_platform_fees'] ?? 0),
                'total_worker_earnings' => (float) ($this->resource['summary']['total_worker_earnings'] ?? 0),
                'total_refunds' => (float) ($this->resource['summary']['total_refunds'] ?? 0),
                'total_profit' => (float) $this->resource['summary']['total_profit'],
                'net_profit' => (float) ($this->resource['summary']['net_profit'] ?? $this->resource['summary']['total_profit']),
            ],
            'chart' => collect($this->resource['chart'])->map(function ($item) {
                return [
                    'date' => $item->date,
                    'revenue' => (float) $item->revenue,
                    'profit' => (float) $item->profit,
                    'platform_fees' => (float) ($item->platform_fees ?? $item->profit),
                    'worker_earnings' => (float) ($item->worker_earnings ?? $item->earnings),
                    'refunds' => (float) ($item->refunds ?? 0),
                    'net_profit' => (float) ($item->net_profit ?? $item->profit),
                ];
            }),
            'top_services' => collect($this->resource['top_services'] ?? [])->map(function ($item) {
                return [
                    'service_id' => $item->service_id ?? null,
                    'service_name' => $item->service_name,
                    'parent_service_name' => $item->parent_service_name ?? null,
                    'total_jobs' => (int) ($item->total_jobs ?? 0),
                    'total_revenue' => (float) $item->total_revenue,
                    'total_platform_fees' => (float) ($item->total_platform_fees ?? $item->total_profit),
                    'total_worker_earnings' => (float) ($item->total_worker_earnings ?? 0),
                    'total_refunds' => (float) ($item->total_refunds ?? 0),
                    'total_profit' => (float) $item->total_profit,
                    'net_profit' => (float) ($item->net_profit ?? $item->total_profit),
                ];
            }),
        ];
    }
}
