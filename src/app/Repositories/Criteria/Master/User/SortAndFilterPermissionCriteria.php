<?php

namespace App\Repositories\Criteria\Master\User;

use App\Models\BaseMasterModel;
use App\Models\UserPermission;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SortAndFilterPermissionCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function __construct(array $filters = [], array $sorts = [], array $search = [])
    {
        parent::__construct($filters, $sorts, $search);
    }

    public function apply($model, RepositoryInterface $repository): BaseMasterModel|Builder|null
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
        return $this->sortByConditions($builder, $this->sorts, [
            'id' => UserPermission::TABLE_NAME.'.id',
            'permission' => UserPermission::TABLE_NAME.'.permission',
            'user_code' => UserPermission::TABLE_NAME.'.user_code',
        ]);
    }

    public function filter($builder): Model|Builder|null
    {
        return $this->filterByConditions($builder, $this->filters, [
            'permission' => UserPermission::TABLE_NAME.'.permission',
            'user_code' => UserPermission::TABLE_NAME.'.user_code',
        ]);
    }

    public function search($builder): Model|Builder|null
    {
        return $this->searchByConditions($builder, $this->searchConditions, [
            'permission' => UserPermission::TABLE_NAME.'.permission',
            'user_code' => UserPermission::TABLE_NAME.'.user_code',
        ]);
    }
}
