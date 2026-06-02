<?php

namespace App\Repositories\Criteria\Common;

use App\Models\BaseMasterModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class AbstractListForAppCriteria
 */
abstract class AbstractListForAppCriteria
{
    /**
     * @var array
     */
    protected $sorts;

    /**
     * @var string
     */
    protected $keyword;

    /**
     * AbstractListForAppCriteria constructor.
     */
    public function __construct(array $sorts, string $keyword)
    {
        $this->sorts = $sorts;
        $this->keyword = $keyword;
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
    abstract public function search($builder);
}
