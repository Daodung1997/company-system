<?php

namespace App\Http\Resources\User\Job;

use App\Http\Resources\Area\AreaSimpleResource;
use App\Http\Resources\ServiceCategory\ServiceCategorySimpleResource;
use App\Http\Resources\User\Quotation\QuotationResource;
use App\Http\Resources\User\Review\ReviewResource;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerJobResource extends JsonResource
{
    public function toArray($request)
    {
        $myQuotation = $this->quotations?->where('worker_id', auth()->id())->first();
        $isAssigned = $this->worker_id == auth()->id();

        $data = [
            'id' => $this->id,
            'code' => $this->code,
            'service' => $this->relationLoaded('serviceCategory') && $this->serviceCategory ? new ServiceCategorySimpleResource($this->serviceCategory) : null,
            'description' => $this->description,
            'media' => $this->when($this->relationLoaded('media'), fn () => JobMediaResource::collection($this->media)),
            'area' => $this->relationLoaded('area') && $this->area ? new AreaSimpleResource($this->area) : null,
            'scheduled_date' => $this->scheduled_date?->format('Y-m-d'),
            'time_slot' => $this->time_slot,
            'status' => $this->status,
            // Customer info - show full details only if assigned
            'customer' => $this->when($this->relationLoaded('customer'), function () use ($isAssigned) {
                return $this->customer ? new WorkerJobCustomerResource($this->customer, $isAssigned) : null;
            }),
            // Worker's own quotation
            'my_quotation' => $myQuotation ? new QuotationResource($myQuotation) : null,
            'address' => $this->address,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'is_invited' => $this->whenLoaded('invitedWorkers', function () {
                return $this->invitedWorkers->where('id', auth()->id())->isNotEmpty();
            }, false),
        ];

        if ($this->relationLoaded('reviews')) {
            $isReviewed = $this->reviews->isNotEmpty();
            $data['is_reviewed'] = $isReviewed;
            if ($isReviewed) {
                $data['review'] = new ReviewResource($this->reviews->first());
            }
        }

        return $data;
    }
}
