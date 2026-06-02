<?php

namespace App\Repositories\Criteria\Role;

use App\Constants\Master\Models\Role\RoleColumn;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;

class SortAndFilterRoleCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function __construct(array $filters = [], array $sorts = [], array $search = [])
    {
        parent::__construct($filters, $sorts, $search);
    }

    public function apply($model, RepositoryInterface $repository)
    {
        $select = ['*'];
        $relationship = ['permissions'];
        $model = $this->filter($model);
        $model = $this->search($model);
        $model = $this->sort($model);

        return $model->select($select)->with($relationship);
    }

    public function sort($builder)
    {
        if (empty($this->sorts)) {
            $this->sorts = [RoleColumn::ID => 'desc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            RoleColumn::ID => 'm_roles.'.RoleColumn::ID,
            RoleColumn::NAME => 'm_roles.'.RoleColumn::NAME,
            RoleColumn::CREATED_AT => 'm_roles.'.RoleColumn::CREATED_AT,
            RoleColumn::UPDATED_AT => 'm_roles.'.RoleColumn::UPDATED_AT,
        ]);
    }

    public function filter($builder)
    {
        return $this->filterByConditions($builder, $this->filters, [
            RoleColumn::ID => 'm_roles.'.RoleColumn::ID,
            RoleColumn::NAME => 'm_roles.'.RoleColumn::NAME,
            RoleColumn::GUARD_NAME => 'm_roles.'.RoleColumn::GUARD_NAME,
        ]);
    }

    public function search($builder)
    {
        return $this->searchByConditions($builder, $this->searchConditions, [
            RoleColumn::NAME => 'm_roles.'.RoleColumn::NAME,
        ]);
    }
}
