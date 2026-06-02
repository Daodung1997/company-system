<?php

namespace App\Http\Resources\User\Quotation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'job_id' => $this->job_id,
            'price' => (float) $this->price,
            'platform_fee' => (float) $this->platform_fee,
            'total_amount' => (float) $this->total_amount,
            'estimated_duration' => $this->estimated_duration,
            'note' => $this->note,
            'status' => $this->status,
            'worker' => $this->when($this->relationLoaded('worker'), function () {
                return $this->worker ? new QuotationWorkerResource($this->worker) : null;
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
