<?php

namespace App\Http\Resources\Admin\Finance;

use App\Constants\Master\Models\Job\JobStatusConst;
use App\Http\Resources\ServiceCategory\ServiceCategoryResource;
use App\Http\Resources\User\Quotation\QuotationResource;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminJobPaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'is_refunded' => $this->status === JobStatusConst::REFUNDED,
            'customer' => $this->customer ? new UserResource($this->customer) : null,
            'worker' => $this->worker ? new UserResource($this->worker) : null,
            'service' => $this->serviceCategory ? new ServiceCategoryResource($this->serviceCategory) : null,
            'quotation' => $this->quotation ? new QuotationResource($this->quotation) : null,
        ];
    }
}
