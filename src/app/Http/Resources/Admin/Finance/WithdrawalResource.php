<?php

namespace App\Http\Resources\Admin\Finance;

use App\Http\Resources\User\UserResource;
use App\Http\Resources\Wallet\WithdrawalLogResource;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'processed_at' => $this->processed_at,
            'created_at' => $this->created_at,
            'worker' => $this->whenLoaded('worker', fn () => new UserResource($this->worker)),
            'bank_account' => $this->whenLoaded('bankAccount', fn () => [
                'id' => $this->bankAccount->id,
                'bank_name' => $this->bankAccount->bank_name,
                'account_number' => $this->bankAccount->account_number,
                'account_holder' => $this->bankAccount->account_name, // Mapping from account_name to account_holder as per docs
            ]),
            'transaction_info' => [
                'processing_method' => 'Gateway', // Default to Gateway as per new flow
                'system_txn_code' => $this->code,
                'gateway_txn_code' => $this->gateway_reference,
                'system_message' => $this->status === \App\Constants\Transaction\Models\Withdrawal\WithdrawalStatusConst::FAILED
                    ? $this->failure_reason
                    : ($this->status === \App\Constants\Transaction\Models\Withdrawal\WithdrawalStatusConst::COMPLETED ? 'Success' : null),
            ],
            'system_logs' => $this->whenLoaded('logs', fn () => WithdrawalLogResource::collection($this->logs)),
        ];
    }
}
