<?php

namespace App\Repositories\Criteria\Master\User;

use App\Models\BaseMasterModel;
use App\Models\CustomerProfile;
use App\Models\User;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;
use Illuminate\Database\Eloquent\Builder;

class SortAndFilterUserCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function __construct(array $filters = [], array $sorts = [], array $search = [])
    {
        parent::__construct($filters, $sorts, $search);
    }

    public function apply($model, RepositoryInterface $repository): BaseMasterModel|Builder|null
    {
        $select = [User::TABLE_NAME.'.*'];
        $relationship = []; // Let caller decide relationships via with()
        $model = $this->_join($model);
        $model = $this->filter($model);
        $model = $this->search($model);
        $model = $this->sort($model);

        return $model->select($select)->with($relationship);
    }

    /**
     * Join customer_profiles table when phone search is needed
     */
    protected function _join($builder): BaseMasterModel|Builder|null
    {
        $hasPhoneSearch = ! empty($this->searchConditions['phone'] ?? null);

        if ($hasPhoneSearch) {
            $builder = $builder->leftJoin(
                CustomerProfile::TABLE_NAME,
                User::TABLE_NAME.'.id',
                '=',
                CustomerProfile::TABLE_NAME.'.user_id'
            );
        }

        return $builder;
    }

    public function sort($builder): BaseMasterModel|Builder|null
    {
        return $this->sortByConditions($builder, $this->sorts, [
            'id' => User::TABLE_NAME.'.id',
            'code' => User::TABLE_NAME.'.code',
            'name' => User::TABLE_NAME.'.name',
            'email' => User::TABLE_NAME.'.email',
            'status' => User::TABLE_NAME.'.status',
            'role' => User::TABLE_NAME.'.role',
        ]);
    }

    public function filter($builder): BaseMasterModel|Builder|null
    {
        return $this->filterByConditions($builder, $this->filters, [
            'id' => User::TABLE_NAME.'.id',
            'code' => User::TABLE_NAME.'.code',
            'name' => User::TABLE_NAME.'.name',
            'email' => User::TABLE_NAME.'.email',
            'status' => User::TABLE_NAME.'.status',
            'role' => User::TABLE_NAME.'.role',
        ]);
    }

    public function search($builder): BaseMasterModel|Builder|null
    {
        $searchFields = [
            'code' => User::TABLE_NAME.'.code',
            'name' => User::TABLE_NAME.'.name',
            'email' => User::TABLE_NAME.'.email',
        ];

        // Add phone search field when joined
        if (! empty($this->searchConditions['phone'] ?? null)) {
            $searchFields['phone'] = CustomerProfile::TABLE_NAME.'.phone';
        }

        return $this->searchByConditions($builder, $this->searchConditions, $searchFields);
    }
}
