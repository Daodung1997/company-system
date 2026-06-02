<?php

namespace App\Services\Admin\Configuration;

use App\Constants\Commons\App;
use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\ServiceCategory\ServiceCategoryLevelConst;
use App\Exceptions\BusinessException;
use App\Models\ServiceCategory;
use App\Repositories\Criteria\Configuration\SortAndFilterServiceCategoryCriteria;
use App\Repositories\ServiceCategory\ServiceCategoryRepository;
use App\Services\AbstractService;
use Exception;

class ServiceCategoryService extends AbstractService
{
    protected $repository;

    public function __construct(ServiceCategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function list(array $params)
    {
        $filters = $params['filters'] ?? [];
        $sorts = $params['sorts'] ?? [];
        $search = $params['search'] ?? [];

        $this->repository->pushCriteria(
            new SortAndFilterServiceCategoryCriteria($filters, $sorts, $search)
        );

        return $this->repository->with(['icon', 'children'])->paginate($params['limit'] ?? App::PER_PAGE);
    }

    public function show(int $id)
    {
        $category = $this->repository->with(['icon', 'parent', 'children.icon'])->find($id);
        if (! $category) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, __('common.not_found'), 404);
        }

        return $category;
    }

    public function create(array $data)
    {
        $this->beginTransaction();
        try {
            // Map icon field
            if (isset($data['icon'])) {
                $data['icon_code'] = $data['icon'];
                unset($data['icon']);
            }

            // Auto-set level based on parent_id
            if (! empty($data['parent_id'])) {
                $parent = $this->repository->find($data['parent_id']);
                if (! $parent || $parent->level !== ServiceCategoryLevelConst::MAIN) {
                    throw new BusinessException(
                        ExceptionCode::INVALID_PARENT,
                        'Parent must be a main category (level 1)',
                        422
                    );
                }
                $data['level'] = ServiceCategoryLevelConst::SUB;
            } else {
                $data['parent_id'] = null;
                $data['level'] = ServiceCategoryLevelConst::MAIN;
                $data['description'] = null; // Main category has no description
            }

            $category = $this->repository->create($data);
            $this->commitTransaction();

            return $category;
        } catch (Exception $e) {
            $this->rollbackTransaction();
            $this->handleException($e);
        }
    }

    public function update(int $id, array $data)
    {
        $this->beginTransaction();
        try {
            $category = $this->repository->find($id);
            if (! $category) {
                throw new BusinessException(ExceptionCode::NOT_FOUND, __('common.not_found'), 404);
            }

            // Reject immutable fields
            if (array_key_exists('level', $data)) {
                throw new BusinessException(
                    ExceptionCode::IMMUTABLE_FIELD,
                    'Cannot change level after creation',
                    422
                );
            }

            if (array_key_exists('parent_id', $data)) {
                if ($category->level === ServiceCategoryLevelConst::MAIN) {
                    throw new BusinessException(
                        ExceptionCode::IMMUTABLE_FIELD,
                        'Cannot change parent_id of a main category',
                        422
                    );
                }

                // FormRequest already verified it exists and is MAIN, but we can double check or just proceed.
            }

            // Map icon field
            if (array_key_exists('icon', $data)) {
                $data['icon_code'] = $data['icon'];
                unset($data['icon']);
            }

            // Main category has no description
            if ($category->level === ServiceCategoryLevelConst::MAIN) {
                $data['description'] = null;
            }

            $this->repository->update($id, $data);
            $category = $this->repository->find($id);
            $this->commitTransaction();

            return $category;
        } catch (Exception $e) {
            $this->rollbackTransaction();
            $this->handleException($e);
        }
    }

    public function delete(int $id)
    {
        $this->beginTransaction();
        try {
            $category = $this->repository->find($id);
            if (! $category) {
                throw new BusinessException(ExceptionCode::NOT_FOUND, __('common.not_found'), 404);
            }

            // Main category: check children
            if ($category->level === ServiceCategoryLevelConst::MAIN) {
                $childrenCount = ServiceCategory::where('parent_id', $category->id)->count();
                if ($childrenCount > 0) {
                    throw new BusinessException(
                        ExceptionCode::HAS_CHILDREN,
                        'Cannot delete main category that has sub-categories',
                        409
                    );
                }
            }

            // Sub category: check jobs and worker services
            if ($category->level === ServiceCategoryLevelConst::SUB) {
                $jobCount = \App\Models\Job::where('service_id', $category->id)->count();
                $workerServiceCount = \App\Models\WorkerService::where('service_category_id', $category->id)->count();
                if ($jobCount > 0 || $workerServiceCount > 0) {
                    throw new BusinessException(
                        ExceptionCode::CATEGORY_IN_USE,
                        'Cannot delete category that is in use by jobs or workers',
                        409
                    );
                }
            }

            $category->delete();
            $this->commitTransaction();

            return true;
        } catch (Exception $e) {
            $this->rollbackTransaction();
            $this->handleException($e);
        }
    }

    public function reorder(array $data)
    {
        $this->beginTransaction();
        try {
            $orderedIds = $data['ordered_ids'];

            // Fetch all categories by IDs
            $categories = $this->repository->findWhereIn('id', $orderedIds)->all();

            if ($categories->count() !== count($orderedIds)) {
                throw new BusinessException(
                    ExceptionCode::NOT_FOUND,
                    'Some categories not found',
                    404
                );
            }

            // Validate all categories belong to same scope (same parent_id)
            $parentIds = $categories->pluck('parent_id')->unique();
            if ($parentIds->count() > 1) {
                throw new BusinessException(
                    ExceptionCode::INVALID_REORDER_SCOPE,
                    'All categories must belong to the same parent',
                    422
                );
            }

            $parentId = $parentIds->first();

            // Validate completeness: ordered_ids must contain ALL categories in scope
            $totalInScope = ServiceCategory::where('parent_id', $parentId)->count();
            if (count($orderedIds) !== $totalInScope) {
                throw new BusinessException(
                    ExceptionCode::INCOMPLETE_REORDER_SET,
                    'Ordered IDs must include all categories in the same scope',
                    422
                );
            }

            // Update sort_order sequentially
            foreach ($orderedIds as $index => $id) {
                $this->repository->update($id, ['sort_order' => $index + 1]);
            }

            $this->commitTransaction();

            return $this->repository->findWhereIn('id', $orderedIds)->all()
                ->sortBy('sort_order')
                ->values();
        } catch (Exception $e) {
            $this->rollbackTransaction();
            $this->handleException($e);
        }
    }

    protected function handleException(Exception $e)
    {
        if ($e instanceof BusinessException) {
            throw $e;
        }

        throw new BusinessException(
            ExceptionCode::UNKNOWN_ERROR,
            $e->getMessage(),
            500
        );
    }
}
