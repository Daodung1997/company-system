<?php

namespace App\Http\Resources\Wallet;

use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'account_name' => $this->account_name,
            'branch' => $this->branch,
            'is_default' => (bool) $this->is_default,
            'created_at' => $this->created_at,
        ];
    }
}
