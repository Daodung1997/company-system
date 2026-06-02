<?php

namespace App\Repositories\Criteria\PaymentMethod;

use App\Constants\Commons\CommonStatusConst;
use App\Constants\Master\Models\PaymentMethod\PaymentMethodColumn;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class ActivePaymentMethodCriteria implements CriteriaInterface
{
    public function apply($model, RepositoryInterface $repository): Builder
    {
        return $model->where('m_payment_methods.'.PaymentMethodColumn::STATUS, CommonStatusConst::ACTIVE)
            ->orderBy('m_payment_methods.'.PaymentMethodColumn::SORT_ORDER, 'asc');
    }
}
