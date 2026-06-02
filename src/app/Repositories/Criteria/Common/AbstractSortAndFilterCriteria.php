<?php

namespace App\Repositories\Criteria\Common;

use App\Models\BaseMasterModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class AbstractSortAndFilterCriteria
 */
abstract class AbstractSortAndFilterCriteria
{
    /**
     * @var array
     */
    protected $filters;

    /**
     * @var array
     */
    protected $sorts;

    /**
     * @var array
     */
    protected $searchConditions;

    protected bool $currentSort = false;

    public function __construct(array $filters = [], array $sorts = [], array $search = [])
    {
        $this->filters = $filters;
        $this->sorts = $sorts;
        $this->searchConditions = $search;
    }

    /**
     * @param  Builder|BaseMasterModel  $builder
     * @return Builder|BaseMasterModel
     */
    abstract public function sort($builder);

    /**
     * @param  Builder|BaseMasterModel  $builder
     * @return Builder|BaseMasterModel
     */
    abstract public function filter($builder);

    /**
     * @param  Builder|BaseMasterModel  $builder
     * @return Builder|BaseMasterModel
     */
    abstract public function search($builder);
}
