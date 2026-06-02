<?php

namespace App\Http\Resources\Wallet;

use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'bank_account' => $this->when($this->relationLoaded('bankAccount'), function () {
                return [
                    'bank_name' => $this->bankAccount->bank_name,
                    'account_number' => '***'.substr($this->bankAccount->account_number, -4),
                ];
            }),
            'status' => $this->status,
            'created_at' => $this->created_at,
            // Extra fields kept for list/detail if needed, but following spec strictly for create
            'code' => $this->when($request->routeIs('*.list', '*.show'), $this->code),
            'failure_reason' => $this->when($request->routeIs('*.list', '*.show'), $this->failure_reason),
            'processed_at' => $this->when($request->routeIs('*.list', '*.show'), $this->processed_at),
        ];
    }
}
