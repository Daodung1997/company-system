<?php

namespace App\Repositories\PlatformFee;

use App\Constants\Master\Models\PlatformFee\PlatformFeeStatusConst;
use App\Models\PlatformFee;
use App\Repositories\Repository;

class PlatformFeeRepository extends Repository
{
    public function __construct(PlatformFee $model)
    {
        parent::__construct($model);
    }

    /**
     * Get the currently active fee by code, considering the start_date and end_date.
     *
     * @return PlatformFee|null
     */
    public function getActiveFeeByCode(string $code)
    {
        $model = $this->getInstance()
            ->where('code', $code)
            ->where('status', PlatformFeeStatusConst::ACTIVE)
            ->where('start_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->orderByDesc('start_date')
            ->first();

        return $model;
    }
}
