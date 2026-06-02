<?php

namespace App\Http\Resources\Customer\Home;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerHomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user' => [
                'name' => $this->user['name'] ?? null,
                'avatar' => $this->user['avatar'] ?? null,
            ],
            'notifications' => [
                'unread_count' => $this->notifications['unread_count'] ?? 0,
            ],
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'ongoing_requests' => OngoingRequestResource::collection($this->whenLoaded('ongoing_requests')),
            'suggested_workers' => $this->suggested_workers ?? [],
            'banners' => $this->banners ?? [],
        ];
    }
}
