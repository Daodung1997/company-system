<?php

namespace App\Http\Resources\User\Payment;

use App\Constants\Master\Models\Payment\PaymentResourceConst;
use App\Http\Resources\User\Job\JobResource;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        $resource = [];

        foreach (PaymentResourceConst::getValues() as $value) {
            if ($value === PaymentResourceConst::JOB) {
                $resource[$value] = $this->whenLoaded('job', function () {
                    return new JobResource($this->job);
                });

                continue;
            }

            if ($value === PaymentResourceConst::PAYMENT_METHOD_DETAIL) {
                $resource[$value] = $this->whenLoaded('paymentMethodDetail', function () {
                    return new PaymentMethodResource($this->paymentMethodDetail);
                });

                continue;
            }

            $resource[$value] = $this->{$value};
        }

        return $resource;
    }
}
