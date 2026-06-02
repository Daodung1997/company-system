<?php

namespace App\Http\Resources\Admin;

use App\Constants\Master\Resource\PermissionResourceConst;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resource = [];

        foreach (PermissionResourceConst::getValues() as $value) {
            $resource[$value] = $this->{$value};
        }

        return $resource;
    }
}
