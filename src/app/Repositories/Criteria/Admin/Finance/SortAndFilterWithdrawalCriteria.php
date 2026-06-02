<?php

namespace App\Repositories\Criteria\Admin\Finance;

use App\Models\BaseModel;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;
use Illuminate\Database\Eloquent\Builder;

class SortAndFilterWithdrawalCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function __construct(array $filters = [], array $sorts = [], array $search = [])
    {
        parent::__construct($filters, $sorts, $search);
    }

    public function apply($model, RepositoryInterface $repository): BaseModel|Builder|null
    {
        $select = ['t_withdrawals.*'];
        $relationship = ['worker', 'bankAccount'];

        $model = $this->filter($model);
        $model = $this->search($model);
        $model = $this->sort($model);

        return $model->select($select)->with($relationship);
    }

    public function filter($builder): BaseModel|Builder|null
    {
        $builder = $this->filterByConditions($builder, $this->filters, [
            'status' => 't_withdrawals.status',
        ]);

        if (! empty($this->filters['date_from'])) {
            $builder = $builder->whereDate('t_withdrawals.created_at', '>=', $this->filters['date_from']);
        }
        if (! empty($this->filters['date_to'])) {
            $builder = $builder->whereDate('t_withdrawals.created_at', '<=', $this->filters['date_to']);
        }

        // Filter by specific bank if needed (not common but possible)
        if (! empty($this->filters['bank_name'])) {
            $builder = $builder->whereHas('bankAccount', function ($query) {
                $query->where('bank_name', $this->filters['bank_name']);
            });
        }

        return $builder;
    }

    public function sort($builder): BaseModel|Builder|null
    {
        if (empty($this->sorts)) {
            $this->sorts = ['created_at' => 'desc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            'id' => 't_withdrawals.id',
            'created_at' => 't_withdrawals.created_at',
            'amount' => 't_withdrawals.amount',
            'status' => 't_withdrawals.status',
        ]);
    }

    public function search($builder): BaseModel|Builder|null
    {
        // Custom search for relations
        $searchData = $this->searchConditions;

        if (! empty($searchData['worker_name'])) {
            $builder->whereHas('worker', function ($q) use ($searchData) {
                $q->where('name', 'LIKE', '%'.$searchData['worker_name'].'%');
            });
        }

        return $this->searchByConditions($builder, $searchData, [
            'code' => 't_withdrawals.code',
        ]);
    }
}
