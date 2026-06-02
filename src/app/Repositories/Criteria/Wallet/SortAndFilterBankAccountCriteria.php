<?php

namespace App\Repositories\Criteria\Wallet;

use App\Models\BaseModel;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;
use Illuminate\Database\Eloquent\Builder;

class SortAndFilterBankAccountCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function apply($model, $repository): BaseModel|Builder|null
    {
        $select = ['m_bank_accounts.*'];
        $relationship = [];

        $model = $this->filter($model);
        $model = $this->search($model);
        $model = $this->sort($model);

        return $model->select($select)->with($relationship);
    }

    public function sort($builder)
    {
        if (empty($this->sorts)) {
            $this->sorts = ['created_at' => 'desc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            'id' => 'm_bank_accounts.id',
            'created_at' => 'm_bank_accounts.created_at',
            'is_default' => 'm_bank_accounts.is_default',
        ]);
    }

    public function filter($builder)
    {
        return $this->filterByConditions($builder, $this->filters, [
            'user_id' => 'm_bank_accounts.user_id',
            'is_default' => 'm_bank_accounts.is_default',
        ]);
    }

    public function search($builder)
    {
        return $this->searchByConditions($builder, $this->searchConditions, [
            'bank_name' => 'm_bank_accounts.bank_name',
            'account_number' => 'm_bank_accounts.account_number',
            'account_name' => 'm_bank_accounts.account_name',
        ]);
    }
}
