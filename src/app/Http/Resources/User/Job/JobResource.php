<?php

namespace App\Http\Resources\User\Job;

use App\Http\Resources\Area\AreaSimpleResource;
use App\Http\Resources\ServiceCategory\ServiceCategorySimpleResource;
use App\Http\Resources\User\Review\ReviewResource;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'code' => $this->code,
            'service' => (method_exists($this->resource, 'relationLoaded') ? $this->relationLoaded('serviceCategory') : true) && $this->serviceCategory ? new ServiceCategorySimpleResource($this->serviceCategory) : null,
            'description' => $this->description,
            'media' => JobMediaResource::collection($this->whenLoaded('media')),
            'address' => $this->address,
            'area' => (method_exists($this->resource, 'relationLoaded') ? $this->relationLoaded('area') : true) && $this->area ? new AreaSimpleResource($this->area) : null,
            'scheduled_date' => $this->scheduled_date->format('Y-m-d'),
            'time_slot' => $this->time_slot,
            'work_time_type' => $this->work_time_type,
            'work_start_time' => $this->work_start_time ? substr($this->work_start_time, 0, 5) : null,
            'work_end_time' => $this->work_end_time ? substr($this->work_end_time, 0, 5) : null,
            'user_address_id' => $this->user_address_id,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'quotation_price' => $this->quotation_price,
            'quotations_count' => $this->quotations->count(),
            'worker' => $this->worker_id && (method_exists($this->resource, 'relationLoaded') ? $this->relationLoaded('worker') : true) && $this->worker ? new JobWorkerResource($this->worker) : null,
            'discount_id' => $this->discount_id,
            'discount_code' => $this->discount_code,
            'discount_amount' => $this->discount_amount,
            'original_amount' => $this->original_amount,
            'final_amount' => $this->final_amount,
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
