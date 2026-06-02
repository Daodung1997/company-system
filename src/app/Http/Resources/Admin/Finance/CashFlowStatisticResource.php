<?php

namespace App\Http\Resources\Admin\Finance;

use Illuminate\Http\Resources\Json\JsonResource;

class CashFlowStatisticResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'summary' => [
                'payment_inflow' => (float) $this->resource['summary']['payment_inflow'],
                'refund_outflow' => (float) $this->resource['summary']['refund_outflow'],
                'withdrawal_outflow' => (float) $this->resource['summary']['withdrawal_outflow'],
                'net_cash_movement' => (float) $this->resource['summary']['net_cash_movement'],
            ],
            'chart' => collect($this->resource['chart'])->map(function ($item) {
                return [
                    'period' => $item['period'],
                    'payment_inflow' => (float) $item['payment_inflow'],
                    'refund_outflow' => (float) $item['refund_outflow'],
                    'withdrawal_outflow' => (float) $item['withdrawal_outflow'],
                    'net_cash_movement' => (float) $item['net_cash_movement'],
                ];
            }),
        ];
    }
}
