<?php

namespace App\Http\Resources\Admin;

use App\Constants\Master\Resource\AdminResourceConst;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resource = [];

        foreach (AdminResourceConst::getValues() as $value) {
            if ($value === AdminResourceConst::ROLES) {
                $resource[$value] = RoleResource::collection($this->whenLoaded('roles'));
            } elseif ($value === AdminResourceConst::PERMISSIONS) {
                if ($this->relationLoaded('roles')) {
                    $permissions = collect();
                    foreach ($this->roles as $role) {
                        if ($role->relationLoaded('permissions')) {
                            $permissions = $permissions->merge($role->permissions);
                        }
                    }
                    $resource[$value] = PermissionResource::collection($permissions->unique('id')->values());
                } else {
                    $resource[$value] = [];
                }
            } else {
                $resource[$value] = $this->{$value};
            }
        }

        return $resource;
    }
}
