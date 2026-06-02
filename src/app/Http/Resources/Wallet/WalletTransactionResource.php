<?php

namespace App\Http\Resources\Wallet;

use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'description' => $this->description,
            'job_id' => $this->job_id,
            'withdrawal_id' => $this->withdrawal_id,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
