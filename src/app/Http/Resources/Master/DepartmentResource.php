<?php

namespace App\Http\Resources\Master;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'job_titles' => $this->relationLoaded('jobTitles') ? $this->jobTitles->map(function ($jobTitle) {
                return [
                    'id' => $jobTitle->id,
                    'code' => $jobTitle->code,
                    'name' => $jobTitle->name,
                    'description' => $jobTitle->description,
                ];
            }) : [],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
