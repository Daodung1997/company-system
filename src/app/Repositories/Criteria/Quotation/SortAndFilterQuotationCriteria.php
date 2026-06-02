<?php

namespace App\Repositories\Criteria\Quotation;

use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;

class SortAndFilterQuotationCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function __construct(array $filters = [], array $sorts = [], array $search = [])
    {
        parent::__construct($filters, $sorts, $search);
    }

    public function apply($model, RepositoryInterface $repository)
    {
        $select = ['t_quotations.*'];
        $relationship = ['worker']; // eager load worker profile/avatar usually needed

        $model = $this->filter($model);
        $model = $this->search($model);
        $model = $this->sort($model);

        return $model->select($select)->with($relationship);
    }

    public function filter($builder)
    {
        $builder = $this->filterByConditions($builder, $this->filters, [
            'status' => 't_quotations.status',
            'job_id' => 't_quotations.job_id',
            'worker_id' => 't_quotations.worker_id',
        ]);

        return $builder;
    }

    public function sort($builder)
    {
        if (empty($this->sorts)) {
            $this->sorts = ['created_at' => 'desc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            'id' => 't_quotations.id',
            'created_at' => 't_quotations.created_at',
            'price' => 't_quotations.price',
        ]);
    }

    public function search($builder)
    {
        return $this->searchByConditions($builder, $this->searchConditions, [
            'code' => 't_quotations.code',
            'note' => 't_quotations.note',
        ]);
    }
}
