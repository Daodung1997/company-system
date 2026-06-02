<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerProfileResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $this['user'];
        $profile = $this['profile'];

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $profile ? $profile->phone : null,
            'avatar' => $profile?->avatar ? new \App\Http\Resources\Common\ImageSimpleResource($profile->avatar) : null,
            'gender' => $profile ? $profile->gender : null,
            'dob' => $profile?->birthday?->format('Y-m-d'),
            'area' => $profile?->area ? new \App\Http\Resources\Area\AreaSimpleResource($profile->area) : null,
            'profile_status' => $this->calculateStatus($user, $profile),
        ];
    }

    protected function calculateStatus($user, $profile)
    {
        if (! $profile) {
            return 'incomplete';
        }
        if ($user->name && $profile->phone && $profile->area_id) {
            return 'completed';
        }

        return 'incomplete';
    }
}
