<?php

namespace App\Http\Resources\Admin;

use App\Constants\Master\Resource\RoleResourceConst;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resource = [];

        foreach (RoleResourceConst::getValues() as $value) {
            if ($value === RoleResourceConst::PERMISSIONS) {
                $resource[$value] = PermissionResource::collection($this->whenLoaded('permissions'));
            } else {
                $resource[$value] = $this->{$value};
            }
        }

        return $resource;
    }
}
