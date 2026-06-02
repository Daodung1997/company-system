<?php

namespace App\Http\Resources\User;

use App\Constants\Master\Models\User\UserRoleConst;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'role' => $this->role,
            'roles' => $this->getRolesMetadata(),
            'user_role' => $this->role ? strtoupper($this->role) : null,
            'is_profile_completed' => $this->checkIsProfileCompleted(),
            'worker_status' => $this->getWorkerStatusMetadata(),
            'profile_status' => $this->when($this->role === UserRoleConst::WORKER, function () {
                return $this->workerProfile?->profile_status;
            }),
            'email_verified_at' => $this->email_verified_at,
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
