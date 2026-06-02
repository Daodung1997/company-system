<?php

namespace App\Http\Resources\Admin\Worker;

use App\Http\Resources\Area\AreaSimpleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerAreaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'area' => $this->area ? new AreaSimpleResource($this->area) : null,
        ];
    }
}
