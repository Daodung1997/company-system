<?php

namespace App\Repositories\Criteria\Discount;

use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SortAndFilterDiscountCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function __construct(array $filters = [], array $sorts = [], array $search = [])
    {
        parent::__construct($filters, $sorts, $search);
    }

    public function apply($model, RepositoryInterface $repository): Model|Builder|null
    {
        $select = ['*'];
        $relationship = [];

        $model = $this->filter($model);
        $model = $this->search($model);
        $model = $this->sort($model);

        return $model->select($select)->with($relationship);
    }

    public function sort($builder): Model|Builder|null
    {
        if (empty($this->sorts)) {
            $this->sorts = ['created_at' => 'desc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            'id' => 'id',
            'code' => 'code',
            'title' => 'title',
            'discount_type' => 'discount_type',
            'discount_value' => 'discount_value',
            'start_date' => 'start_date',
            'end_date' => 'end_date',
            'status' => 'status',
            'created_at' => 'created_at',
        ]);
    }

    public function filter($builder)
    {
        $mappedFilters = $this->filters;
        if (isset($mappedFilters['status'])) {
            if (strtoupper($mappedFilters['status']) === 'ACTIVE') {
                $mappedFilters['status'] = 1;
            } elseif (strtoupper($mappedFilters['status']) === 'INACTIVE') {
                $mappedFilters['status'] = 2;
            }
        }

        return $this->filterByConditions($builder, $mappedFilters, [
            'status' => 'status',
            'discount_type' => 'discount_type',
        ]);
    }

    public function search($builder)
    {
        return $this->searchByConditions($builder, $this->searchConditions, [
            'code' => 'code',
            'title' => 'title',
        ]);
    }
}
