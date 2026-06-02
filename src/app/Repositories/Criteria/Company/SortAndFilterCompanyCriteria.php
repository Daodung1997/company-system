<?php

namespace App\Repositories\Criteria\Company;

use App\Models\Company;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;

class SortAndFilterCompanyCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function __construct(array $filters = [], array $sorts = [], array $search = [])
    {
        parent::__construct($filters, $sorts, $search);
    }

    public function apply($model, RepositoryInterface $repository)
    {
        $select = ['*'];
        $model = $this->filter($model);
        $model = $this->search($model);
        $model = $this->sort($model);

        return $model->select($select);
    }

    public function sort($builder)
    {
        if (empty($this->sorts)) {
            $this->sorts = ['id' => 'desc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            'id' => Company::TABLE_NAME.'.id',
            'code' => Company::TABLE_NAME.'.code',
            'name' => Company::TABLE_NAME.'.name',
            'created_at' => Company::TABLE_NAME.'.created_at',
        ]);
    }

    public function filter($builder)
    {
        return $this->filterByConditions($builder, $this->filters, [
            'status' => Company::TABLE_NAME.'.status',
        ]);
    }

    public function search($builder)
    {
        // Accept both "q" (common query) or specific search fields
        $searchTerms = $this->searchConditions;
        if (isset($searchTerms['q']) && !empty($searchTerms['q'])) {
            $queryVal = $searchTerms['q'];
            return $builder->where(function ($q) use ($queryVal) {
                $q->where(Company::TABLE_NAME.'.name', 'like', "%{$queryVal}%")
                  ->orWhere(Company::TABLE_NAME.'.code', 'like', "%{$queryVal}%")
                  ->orWhere(Company::TABLE_NAME.'.tax_code', 'like', "%{$queryVal}%")
                  ->orWhere(Company::TABLE_NAME.'.legal_representative', 'like', "%{$queryVal}%");
            });
        }

        return $this->searchByConditions($builder, $searchTerms, [
            'name' => Company::TABLE_NAME.'.name',
            'code' => Company::TABLE_NAME.'.code',
            'tax_code' => Company::TABLE_NAME.'.tax_code',
        ]);
    }
}
