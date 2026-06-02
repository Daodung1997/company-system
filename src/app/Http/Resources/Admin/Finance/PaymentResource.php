<?php

namespace App\Http\Resources\Admin\Finance;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'total_amount' => (float) $this->amount,
            'platform_fee' => (float) $this->platform_fee,
            'worker_earning' => (float) $this->worker_earning,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'transaction_id' => $this->transaction_reference,
            'paid_at' => $this->paid_at,
            'refunded_at' => $this->refunded_at,
            'refunded_amount' => $this->refunded_amount ? (float) $this->refunded_amount : null,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'job' => $this->whenLoaded('job', fn () => new AdminJobPaymentResource($this->job)),
            'complaint' => $this->whenLoaded('job', function () {
                if ($this->job && $this->job->relationLoaded('complaints') && $this->job->complaints->isNotEmpty()) {
                    $complaint = $this->job->complaints->first();

                    return [
                        'id' => $complaint->id,
                        'code' => $complaint->code,
                        'status' => $complaint->status,
                        'reason' => $complaint->reason,
                    ];
                }

                return null;
            }),
        ];
    }
}
