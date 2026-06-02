<?php

namespace App\Repositories\Contracts;

use App\Models\AbstractModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Interface CriteriaInterface
 */
interface CriteriaInterface
{
    /**
     * Apply criteria in query repository
     *
     * @param  Builder|AbstractModel  $model
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository);
}
