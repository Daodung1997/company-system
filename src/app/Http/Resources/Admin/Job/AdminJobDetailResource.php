<?php

namespace App\Http\Resources\Admin\Job;

use App\Http\Resources\Area\AreaSimpleResource;
use App\Http\Resources\ServiceCategory\ServiceCategorySimpleResource;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminJobDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'description' => $this->description,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'service' => $this->relationLoaded('serviceCategory') && $this->serviceCategory ? new ServiceCategorySimpleResource($this->serviceCategory) : null,
            'area' => $this->relationLoaded('area') && $this->area ? new AreaSimpleResource($this->area) : null,
            'scheduled_date' => $this->scheduled_date?->format('Y-m-d'),
            'time_slot' => $this->time_slot,
            'media' => AdminJobMediaResource::collection($this->whenLoaded('media')),
            'customer' => $this->whenLoaded('customer', fn () => new UserResource($this->customer)),
            'worker' => $this->whenLoaded('worker', fn () => $this->worker ? new UserResource($this->worker) : null),
            'quotations' => AdminJobQuotationResource::collection($this->whenLoaded('quotations')),
            'complaints' => AdminJobComplaintResource::collection($this->whenLoaded('complaints')),
            'reviews' => AdminJobReviewResource::collection($this->whenLoaded('reviews')),
            'invited_workers' => AdminJobInvitedWorkerResource::collection($this->whenLoaded('invitedWorkers')),
            'notes' => AdminJobNoteResource::collection($this->whenLoaded('notes')),
            'quotation_price' => $this->quotation_price,
            'platform_fee' => $this->platform_fee,
            'total_amount' => $this->total_amount,
            'payment_details' => $this->whenLoaded('payment', fn () => $this->payment ? [
                'payment_status' => $this->payment->status,
                'payment_method' => $this->payment->payment_method,
                'transaction_id' => $this->payment->transaction_reference,
                'paid_at' => $this->payment->paid_at?->toIso8601String(),
            ] : null),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'cancelled_reason' => $this->cancelled_reason,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
