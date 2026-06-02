<?php

namespace App\Services\ServiceCategory;

use App\Constants\Master\Models\ServiceCategory\ServiceCategoryStatusConst;
use App\Repositories\ServiceCategory\ServiceCategoryRepository;
use App\Services\AbstractService;

class ServiceCategoryService extends AbstractService
{
    public function __construct(
        protected ServiceCategoryRepository $repository
    ) {}

    /**
     * List all active categories with optional filtering, searching, and sorting.
     */
    public function listActive(array $params = [])
    {
        $filters = $params['filters'] ?? [];
        $sorts = $params['sorts'] ?? [];
        $search = $params['search'] ?? [];

        // Luôn luôn force status=active cho frontend
        $filters['status'] = ServiceCategoryStatusConst::ACTIVE;

        $this->repository->pushCriteria(
            new \App\Repositories\Criteria\Configuration\SortAndFilterServiceCategoryCriteria($filters, $sorts, $search)
        );

        return $this->repository
            ->with(['icon'])
            ->paginate($params['limit'] ?? \App\Constants\Commons\App::PER_PAGE);
    }
}
