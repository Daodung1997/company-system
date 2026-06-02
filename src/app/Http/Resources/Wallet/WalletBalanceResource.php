<?php

namespace App\Http\Resources\Wallet;

use Illuminate\Http\Resources\Json\JsonResource;

class WalletBalanceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'available_balance' => (float) $this->resource['available_balance'],
            'pending_balance' => (float) $this->resource['pending_balance'],
            'total_earnings' => (float) $this->resource['total_earnings'],
            'total_withdrawn' => (float) $this->resource['total_withdrawn'],
        ];
    }
}
