<?php

namespace App\Repositories\Criteria\Employee;

use App\Constants\Master\Models\Employee\EmployeeColumn;
use App\Models\Employee;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;

class SortAndFilterEmployeeCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function __construct(array $filters = [], array $sorts = [], array $search = [])
    {
        parent::__construct($filters, $sorts, $search);
    }

    public function apply($model, RepositoryInterface $repository)
    {
        $select = ['*'];
        $relationship = ['department', 'jobTitle'];
        $model = $this->filter($model);
        $model = $this->search($model);
        $model = $this->sort($model);

        return $model->select($select)->with($relationship)->withCount('relatives');
    }

    public function sort($builder)
    {
        if (empty($this->sorts)) {
            $this->sorts = [EmployeeColumn::ID => 'desc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            EmployeeColumn::ID => Employee::TABLE_NAME.'.'.EmployeeColumn::ID,
            EmployeeColumn::CODE => Employee::TABLE_NAME.'.'.EmployeeColumn::CODE,
            EmployeeColumn::FULL_NAME => Employee::TABLE_NAME.'.'.EmployeeColumn::FULL_NAME,
            EmployeeColumn::JOIN_DATE => Employee::TABLE_NAME.'.'.EmployeeColumn::JOIN_DATE,
            EmployeeColumn::CREATED_AT => Employee::TABLE_NAME.'.'.EmployeeColumn::CREATED_AT,
        ]);
    }

    public function filter($builder)
    {
        return $this->filterByConditions($builder, $this->filters, [
            EmployeeColumn::DEPARTMENT_ID => Employee::TABLE_NAME.'.'.EmployeeColumn::DEPARTMENT_ID,
            EmployeeColumn::STATUS => Employee::TABLE_NAME.'.'.EmployeeColumn::STATUS,
            EmployeeColumn::ROLE => Employee::TABLE_NAME.'.'.EmployeeColumn::ROLE,
        ]);
    }

    public function search($builder)
    {
        return $this->searchByConditions($builder, $this->searchConditions, [
            EmployeeColumn::FULL_NAME => [
                Employee::TABLE_NAME.'.'.EmployeeColumn::FULL_NAME,
                Employee::TABLE_NAME.'.'.EmployeeColumn::FULL_NAME_KANA,
                Employee::TABLE_NAME.'.'.EmployeeColumn::ROMAJI_NAME,
            ],
            EmployeeColumn::EMAIL => Employee::TABLE_NAME.'.'.EmployeeColumn::EMAIL,
            EmployeeColumn::PHONE => Employee::TABLE_NAME.'.'.EmployeeColumn::PHONE,
            EmployeeColumn::CODE => Employee::TABLE_NAME.'.'.EmployeeColumn::CODE,
        ]);
    }
}
