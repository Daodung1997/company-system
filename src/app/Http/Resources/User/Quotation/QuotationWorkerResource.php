<?php

namespace App\Http\Resources\User\Quotation;

use App\Http\Resources\Common\ImageSimpleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationWorkerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar_code' => $this->workerProfile?->avatar_code,
            'avatar' => $this->workerProfile?->avatar ? new ImageSimpleResource($this->workerProfile->avatar) : null,
            'rating' => $this->workerProfile?->average_rating,
        ];
    }
}
