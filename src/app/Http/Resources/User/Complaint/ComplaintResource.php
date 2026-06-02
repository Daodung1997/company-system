<?php

namespace App\Http\Resources\User\Complaint;

use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'job_id' => $this->job_id,
            'description' => $this->description,
            'status' => $this->status,
            'resolution_note' => $this->resolution_note,
            'created_at' => $this->created_at,
        ];
    }
}
