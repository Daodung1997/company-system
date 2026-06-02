<?php

namespace App\Http\Resources\Admin\Customer;

use App\Constants\Commons\GenderConst;
use App\Http\Resources\Area\AreaSimpleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'phone' => $this->phone ?? null,
            'address' => $this->address ?? null,
            'area' => $this->area ? new AreaSimpleResource($this->area) : null,
            'gender' => isset($this->gender) ? GenderConst::toString($this->gender) : null,
            'birthday' => isset($this->birthday) ? $this->birthday->format('Y-m-d\TH:i:s.000000\Z') : null,
            'avatar_code' => $this->avatar_code ?? null,
        ];
    }
}
