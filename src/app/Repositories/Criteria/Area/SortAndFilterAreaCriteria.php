<?php

namespace App\Repositories\Criteria\Area;

use App\Models\BaseMasterModel;
use App\Repositories\Contracts\CriteriaInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Criteria\Common\AbstractSortAndFilterCriteria;
use App\Traits\SortFilterSearchCriteria;
use Illuminate\Database\Eloquent\Builder;

class SortAndFilterAreaCriteria extends AbstractSortAndFilterCriteria implements CriteriaInterface
{
    use SortFilterSearchCriteria;

    public function apply($model, RepositoryInterface $repository): BaseMasterModel|Builder|null
    {
        $model = $this->filter($model);
        $model = $this->sort($model);

        return $model->select(['*']);
    }

    public function sort($builder)
    {
        if (empty($this->sorts)) {
            $this->sorts = ['sort_order' => 'asc', 'name' => 'asc'];
        }

        return $this->sortByConditions($builder, $this->sorts, [
            'id' => 'm_areas.id',
            'name' => 'm_areas.name',
            'sort_order' => 'm_areas.sort_order',
        ]);
    }

    public function filter($builder)
    {
        return $this->filterByConditions($builder, $this->filters, [
            'level' => 'm_areas.level',
            'parent_id' => 'm_areas.parent_id',
            'status' => 'm_areas.status',
        ]);
    }

    public function search($builder)
    {
        return $builder;
    }
}
