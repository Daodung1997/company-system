<?php

namespace App\Repositories\Criteria\User\UserAddress;

use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;

class SortAndFilterUserAddressCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function __construct(array $filters = [], array $sorts = [], array $search = [])
    {
        $this->filters = $filters;
        $this->sorts = $sorts;
        $this->searchConditions = $search;
    }

    public function apply($model, RepositoryInterface $repository)
    {
        $select = ['*'];
        $relationship = ['area', 'ward'];
        $model = $this->filter($model);
        $model = $this->sort($model);

        return $model->select($select)->with($relationship);
    }

    public function sort($builder)
    {
        if (empty($this->sorts)) {
            $this->sorts = ['is_default' => 'desc', 'updated_at' => 'desc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            'is_default' => 'm_user_addresses.is_default',
            'updated_at' => 'm_user_addresses.updated_at',
            'created_at' => 'm_user_addresses.created_at',
        ]);
    }

    public function filter($builder)
    {
        return $this->filterByConditions($builder, $this->filters, [
            'is_default' => 'm_user_addresses.is_default',
        ]);
    }

    public function search($builder)
    {
        return $builder;
    }
}
