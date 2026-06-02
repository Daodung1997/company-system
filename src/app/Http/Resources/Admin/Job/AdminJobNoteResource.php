<?php

namespace App\Http\Resources\Admin\Job;

use App\Http\Resources\Admin\AdminResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminJobNoteResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'note' => $this->note,
            'admin' => $this->whenLoaded('admin', fn () => new AdminResource($this->admin)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
