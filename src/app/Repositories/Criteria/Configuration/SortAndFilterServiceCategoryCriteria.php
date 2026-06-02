<?php

namespace App\Repositories\Criteria\Configuration;

use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SortAndFilterServiceCategoryCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
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
            $this->sorts = ['sort_order' => 'asc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            'id' => 'id',
            'name' => 'name',
            'code' => 'code',
            'status' => 'status',
            'sort_order' => 'sort_order',
            'created_at' => 'created_at',
        ]);
    }

    public function filter($builder)
    {
        return $this->filterByConditions($builder, $this->filters, [
            'status' => 'status',
            'parent_id' => 'parent_id',
            'level' => 'level',
        ]);
    }

    public function search($builder)
    {
        return $this->searchByConditions($builder, $this->searchConditions, [
            'name' => 'name',
            'code' => 'code',
        ]);
    }
}
