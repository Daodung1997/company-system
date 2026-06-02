<?php

namespace App\Repositories\Criteria\Master\Customer;

use App\Constants\Master\Models\Customer\CustomerColumn;
use App\Models\BaseMasterModel;
use App\Models\Customer;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SortAndFilterCustomerCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
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

    public function sort($builder): BaseMasterModel|Builder|null
    {
        if (empty($this->sorts)) {
            $this->sorts = [CustomerColumn::ID => 'desc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            CustomerColumn::ID => Customer::TABLE_NAME.'.'.CustomerColumn::ID,
            CustomerColumn::CODE => Customer::TABLE_NAME.'.'.CustomerColumn::CODE,
            CustomerColumn::FIRST_NAME => Customer::TABLE_NAME.'.'.CustomerColumn::FIRST_NAME,
            CustomerColumn::LAST_NAME => Customer::TABLE_NAME.'.'.CustomerColumn::LAST_NAME,
            CustomerColumn::PHONE => Customer::TABLE_NAME.'.'.CustomerColumn::PHONE,
            CustomerColumn::EMAIL => Customer::TABLE_NAME.'.'.CustomerColumn::EMAIL,
            CustomerColumn::ADDRESS => Customer::TABLE_NAME.'.'.CustomerColumn::ADDRESS,
            CustomerColumn::MEMBER_TYPE => Customer::TABLE_NAME.'.'.CustomerColumn::MEMBER_TYPE,
            CustomerColumn::STATUS => Customer::TABLE_NAME.'.'.CustomerColumn::STATUS,
            CustomerColumn::NOTE => Customer::TABLE_NAME.'.'.CustomerColumn::NOTE,
        ]);
    }

    public function filter($builder): BaseMasterModel|Builder|null
    {
        return $this->filterByConditions($builder, $this->filters, [
            CustomerColumn::ID => Customer::TABLE_NAME.'.'.CustomerColumn::ID,
            CustomerColumn::CODE => Customer::TABLE_NAME.'.'.CustomerColumn::CODE,
            CustomerColumn::FIRST_NAME => Customer::TABLE_NAME.'.'.CustomerColumn::FIRST_NAME,
            CustomerColumn::LAST_NAME => Customer::TABLE_NAME.'.'.CustomerColumn::LAST_NAME,
            CustomerColumn::PHONE => Customer::TABLE_NAME.'.'.CustomerColumn::PHONE,
            CustomerColumn::EMAIL => Customer::TABLE_NAME.'.'.CustomerColumn::EMAIL,
            CustomerColumn::ADDRESS => Customer::TABLE_NAME.'.'.CustomerColumn::ADDRESS,
            CustomerColumn::MEMBER_TYPE => Customer::TABLE_NAME.'.'.CustomerColumn::MEMBER_TYPE,
            CustomerColumn::STATUS => Customer::TABLE_NAME.'.'.CustomerColumn::STATUS,
            CustomerColumn::NOTE => Customer::TABLE_NAME.'.'.CustomerColumn::NOTE,
        ]);
    }

    public function search($builder): BaseMasterModel|Builder|null
    {
        return $this->searchByConditions($builder, $this->searchConditions, [
            CustomerColumn::CODE => Customer::TABLE_NAME.'.'.CustomerColumn::CODE,
            CustomerColumn::FULL_NAME => DB::raw('CONCAT('.Customer::TABLE_NAME.'.'.CustomerColumn::FIRST_NAME.", ' ', ".
                Customer::TABLE_NAME.'.'.CustomerColumn::LAST_NAME.')'),
            CustomerColumn::PHONE => Customer::TABLE_NAME.'.'.CustomerColumn::PHONE,
            CustomerColumn::EMAIL => Customer::TABLE_NAME.'.'.CustomerColumn::EMAIL,
            CustomerColumn::ADDRESS => Customer::TABLE_NAME.'.'.CustomerColumn::ADDRESS,
        ]);
    }
}
