<?php

namespace App\Repositories\Criteria\Department;

use App\Models\Department;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;

class SortAndFilterDepartmentCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function __construct(array $filters = [], array $sorts = [], array $search = [])
    {
        parent::__construct($filters, $sorts, $search);
    }

    public function apply($model, RepositoryInterface $repository)
    {
        $select = ['*'];
        $relationship = [];
        $model = $this->filter($model);
        $model = $this->search($model);
        $model = $this->sort($model);

        return $model->select($select)->with($relationship);
    }

    public function sort($builder)
    {
        if (empty($this->sorts)) {
            $this->sorts = ['id' => 'desc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            'id' => Department::TABLE_NAME.'.id',
            'code' => Department::TABLE_NAME.'.code',
            'name' => Department::TABLE_NAME.'.name',
            'created_at' => Department::TABLE_NAME.'.created_at',
        ]);
    }

    public function filter($builder)
    {
        return $this->filterByConditions($builder, $this->filters, []);
    }

    public function search($builder)
    {
        $searchTerms = $this->searchConditions;
        if (isset($searchTerms['q']) && !empty($searchTerms['q'])) {
            $queryVal = $searchTerms['q'];
            return $builder->where(function ($q) use ($queryVal) {
                $q->where(Department::TABLE_NAME.'.name', 'like', "%{$queryVal}%")
                  ->orWhere(Department::TABLE_NAME.'.code', 'like', "%{$queryVal}%")
                  ->orWhere(Department::TABLE_NAME.'.description', 'like', "%{$queryVal}%");
            });
        }

        return $this->searchByConditions($builder, $searchTerms, [
            'name' => Department::TABLE_NAME.'.name',
            'code' => Department::TABLE_NAME.'.code',
        ]);
    }
}
