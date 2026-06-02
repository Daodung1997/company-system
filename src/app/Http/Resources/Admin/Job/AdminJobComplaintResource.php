<?php

namespace App\Http\Resources\Admin\Job;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminJobComplaintResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'description' => $this->description,
            'status' => $this->status,
            'resolution_note' => $this->resolution_note,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
