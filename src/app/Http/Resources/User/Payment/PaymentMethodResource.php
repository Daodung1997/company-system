<?php

namespace App\Http\Resources\User\Payment;

use App\Constants\Commons\CommonStatusConst;
use App\Constants\Commons\Resource\CommonResourceConst;
use App\Constants\Master\Models\PaymentMethod\PaymentMethodResourceConst;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        $resource = [];

        foreach (PaymentMethodResourceConst::getValues() as $value) {
            $resource[$value] = $this->{$value};
        }

        // Add common fields as per spec
        $resource[CommonResourceConst::CREATED_AT] = $this->{CommonResourceConst::CREATED_AT};
        $resource[CommonResourceConst::UPDATED_AT] = $this->{CommonResourceConst::UPDATED_AT};

        // Custom field as per spec: is_enabled (integer 1 or 0)
        $resource['is_enabled'] = $this->status == CommonStatusConst::ACTIVE ? 1 : 0;

        return $resource;
    }
}
