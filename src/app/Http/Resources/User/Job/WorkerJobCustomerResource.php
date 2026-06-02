<?php

namespace App\Http\Resources\User\Job;

use App\Http\Resources\Area\AreaSimpleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerJobCustomerResource extends JsonResource
{
    private bool $isAssigned;

    public function __construct($resource, bool $isAssigned = false)
    {
        parent::__construct($resource);
        $this->isAssigned = $isAssigned;
    }

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->isAssigned ? $this->phone : null,
            'avatar' => $this->customerProfile?->avatar_url,
            'rating' => (float) ($this->customerProfile?->rating ?? 0),
            'area' => $this->customerProfile?->area ? new AreaSimpleResource($this->customerProfile->area) : null,
        ];
    }
}
