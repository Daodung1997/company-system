<?php

namespace App\Http\Resources\Admin\Job;

use App\Http\Resources\Area\AreaSimpleResource;
use App\Http\Resources\ServiceCategory\ServiceCategorySimpleResource;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminJobListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'description' => $this->description,
            'address' => $this->address,
            'service' => $this->relationLoaded('serviceCategory') && $this->serviceCategory ? new ServiceCategorySimpleResource($this->serviceCategory) : null,
            'area' => $this->relationLoaded('area') && $this->area ? new AreaSimpleResource($this->area) : null,
            'customer' => $this->whenLoaded('customer', fn () => new UserResource($this->customer)),
            'worker' => $this->whenLoaded('worker', fn () => $this->worker ? new UserResource($this->worker) : null),
            'scheduled_date' => $this->scheduled_date?->format('Y-m-d'),
            'time_slot' => $this->time_slot,
            'payment_status' => $this->whenLoaded('payment', fn () => $this->payment ? $this->payment->status : 'unpaid', 'unpaid'),
            'quotation_price' => $this->quotation_price,
            'quotations_count' => $this->whenLoaded('quotations', fn () => $this->quotations->count(), 0),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
