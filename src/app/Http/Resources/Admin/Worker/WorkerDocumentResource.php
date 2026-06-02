<?php

namespace App\Http\Resources\Admin\Worker;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkerDocumentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'file_url' => $this->file?->getUrl(),
            'status' => $this->status,
            'uploaded_at' => $this->created_at,
        ];
    }
}
