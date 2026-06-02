<?php

namespace App\Repositories\Criteria\Wallet;

use App\Models\BaseMasterModel;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class SortAndFilterWalletTransactionCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function apply($model, $repository): BaseMasterModel|Builder|null
    {
        $select = ['t_wallet_transactions.*'];
        $relationship = [];

        $model = $this->filter($model);
        $model = $this->search($model);
        $model = $this->sort($model);

        return $model->select($select)->with($relationship);
    }

    public function sort($builder)
    {
        if (empty($this->sorts)) {
            $this->sorts = ['created_at' => 'desc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            'id' => 't_wallet_transactions.id',
            'created_at' => 't_wallet_transactions.created_at',
            'amount' => 't_wallet_transactions.amount',
        ]);
    }

    public function filter($builder)
    {
        $builder = $this->filterByConditions($builder, Arr::except($this->filters, ['date_from', 'date_to']), [
            'worker_id' => 't_wallet_transactions.worker_id',
            'type' => 't_wallet_transactions.type',
            'status' => 't_wallet_transactions.status',
        ]);

        if (! empty($this->filters['date_from'])) {
            $builder = $builder->whereDate('t_wallet_transactions.created_at', '>=', $this->filters['date_from']);
        }

        if (! empty($this->filters['date_to'])) {
            $builder = $builder->whereDate('t_wallet_transactions.created_at', '<=', $this->filters['date_to']);
        }

        return $builder;
    }

    public function search($builder)
    {
        return $this->searchByConditions($builder, $this->searchConditions, [
            'code' => 't_wallet_transactions.code',
        ]);
    }
}
