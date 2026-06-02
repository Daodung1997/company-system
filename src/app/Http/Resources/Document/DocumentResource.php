<?php

namespace App\Http\Resources\Document;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'origin_name' => $this->origin_name,
            'file_path' => $this->file_path,
            'disk' => $this->disk,
            'extension' => $this->extension,
            'filesize' => $this->filesize,
            'documentable_id' => $this->documentable_id,
            'documentable_type' => $this->documentable_type,
            'employee_id' => $this->employee_id,
            'contract_id' => $this->contract_id,
            'transaction_id' => $this->transaction_id,
            'status' => $this->status,
            'url' => $this->url(),
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toISOString() : null,
        ];
    }
}
