<?php

namespace App\Repositories\Criteria\Payment;

use App\Constants\Master\Models\Payment\PaymentColumn;
use App\Constants\Master\Models\Payment\PaymentRelation;
use App\Models\BaseMasterModel;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;
use Illuminate\Database\Eloquent\Builder;

class SortAndFilterPaymentCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function __construct(array $filters = [], array $sorts = [], array $search = [])
    {
        parent::__construct($filters, $sorts, $search);
    }

    public function apply($model, RepositoryInterface $repository): BaseMasterModel|Builder|null
    {
        $select = ['t_payments.*'];
        $relationship = [PaymentRelation::JOB];

        $model = $this->filter($model);
        $model = $this->search($model);
        $model = $this->sort($model);

        return $model->select($select)->with($relationship);
    }

    public function filter($builder): BaseMasterModel|Builder|null
    {
        $standardFilters = [
            PaymentColumn::STATUS => 't_payments.'.PaymentColumn::STATUS,
            PaymentColumn::JOB_ID => 't_payments.'.PaymentColumn::JOB_ID,
            PaymentColumn::PAYMENT_METHOD => 't_payments.'.PaymentColumn::PAYMENT_METHOD,
        ];

        return $this->filterByConditions($builder, $this->filters, $standardFilters);
    }

    public function sort($builder): BaseMasterModel|Builder|null
    {
        if (empty($this->sorts)) {
            $this->sorts = [PaymentColumn::CREATED_AT => 'desc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            PaymentColumn::ID => 't_payments.'.PaymentColumn::ID,
            PaymentColumn::CREATED_AT => 't_payments.'.PaymentColumn::CREATED_AT,
            PaymentColumn::AMOUNT => 't_payments.'.PaymentColumn::AMOUNT,
            PaymentColumn::PAID_AT => 't_payments.'.PaymentColumn::PAID_AT,
        ]);
    }

    public function search($builder): BaseMasterModel|Builder|null
    {
        return $this->searchByConditions($builder, $this->searchConditions, [
            PaymentColumn::CODE => 't_payments.'.PaymentColumn::CODE,
            PaymentColumn::TRANSACTION_REFERENCE => 't_payments.'.PaymentColumn::TRANSACTION_REFERENCE,
        ]);
    }
}
