<?php

namespace App\Http\Resources\Worker\Home;

use App\Http\Resources\User\Job\WorkerJobResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerHomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'worker' => [
                'name' => $this->worker['name'] ?? null,
                'avatar' => $this->worker['avatar'] ?? null,
                'is_online' => $this->worker['is_online'] ?? false,
            ],
            'summary' => [
                'active_jobs' => $this->summary['active_jobs'] ?? 0,
                'pending_quotes' => $this->summary['pending_quotes'] ?? 0,
                'in_progress' => $this->summary['in_progress'] ?? 0,
                'completed' => $this->summary['completed'] ?? 0,
            ],
            'suggested_jobs' => WorkerJobResource::collection($this->suggested_jobs),
            'my_jobs' => WorkerJobResource::collection($this->my_jobs),
        ];
    }
}
