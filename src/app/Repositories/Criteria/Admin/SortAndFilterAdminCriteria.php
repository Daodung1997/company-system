<?php

namespace App\Repositories\Criteria\Admin;

use App\Constants\Master\Models\Admin\AdminColumn;
use App\Models\Admin;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;

class SortAndFilterAdminCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function __construct(array $filters = [], array $sorts = [], array $search = [])
    {
        parent::__construct($filters, $sorts, $search);
    }

    public function apply($model, RepositoryInterface $repository)
    {
        $select = ['*'];
        $relationship = ['roles.permissions'];
        $model = $this->filter($model);
        $model = $this->search($model);
        $model = $this->sort($model);

        return $model->select($select)->with($relationship);
    }

    public function sort($builder)
    {
        if (empty($this->sorts)) {
            $this->sorts = [AdminColumn::ID => 'desc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            AdminColumn::ID => Admin::TABLE_NAME.'.'.AdminColumn::ID,
            AdminColumn::CODE => Admin::TABLE_NAME.'.'.AdminColumn::CODE,
            AdminColumn::CREATED_AT => Admin::TABLE_NAME.'.'.AdminColumn::CREATED_AT,
            AdminColumn::UPDATED_AT => Admin::TABLE_NAME.'.'.AdminColumn::UPDATED_AT,
        ]);
    }

    public function filter($builder)
    {
        if (isset($this->filters['role_id'])) {
            $roleId = $this->filters['role_id'];
            $builder->whereHas('roles', function ($q) use ($roleId) {
                $q->where('m_roles.id', $roleId);
            });
            unset($this->filters['role_id']);
        }

        return $this->filterByConditions($builder, $this->filters, [
            AdminColumn::ID => Admin::TABLE_NAME.'.'.AdminColumn::ID,
            AdminColumn::CODE => Admin::TABLE_NAME.'.'.AdminColumn::CODE,
            AdminColumn::STATUS => Admin::TABLE_NAME.'.'.AdminColumn::STATUS,
            AdminColumn::EMAIL => Admin::TABLE_NAME.'.'.AdminColumn::EMAIL,
        ]);
    }

    public function search($builder)
    {
        return $this->searchByConditions($builder, $this->searchConditions, [
            AdminColumn::CODE => Admin::TABLE_NAME.'.'.AdminColumn::CODE,
            AdminColumn::NAME => Admin::TABLE_NAME.'.'.AdminColumn::NAME,
            AdminColumn::EMAIL => Admin::TABLE_NAME.'.'.AdminColumn::EMAIL,
        ]);
    }
}
