<?php

namespace App\Http\Resources\Admin\Worker;

use App\Http\Resources\ServiceCategory\ServiceCategorySimpleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerServiceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'service_category' => $this->serviceCategory ? new ServiceCategorySimpleResource($this->serviceCategory) : null,
        ];
    }
}
