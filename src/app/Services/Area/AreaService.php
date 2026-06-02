<?php

namespace App\Services\Area;

use App\Repositories\Area\AreaRepository;
use App\Repositories\Criteria\Area\SortAndFilterAreaCriteria;
use App\Services\AbstractService;
use Illuminate\Database\Eloquent\Collection;

class AreaService extends AbstractService
{
    public function __construct(
        protected AreaRepository $repository
    ) {}

    /**
     * List areas by level or parent_id.
     */
    public function list(array $filters): Collection
    {
        // Always filter active areas
        $filters['status'] = 'active';

        return $this->repository->pushCriteria(
            new SortAndFilterAreaCriteria($filters)
        )->all();
    }
}
