<?php

namespace App\Services\Department;

use App\Constants\Commons\ExceptionCode;
use App\Exceptions\BusinessException;
use App\Repositories\Department\DepartmentRepository;
use App\Repositories\Criteria\Department\SortAndFilterDepartmentCriteria;
use App\Services\AbstractService;

class DepartmentService extends AbstractService
{
    public function __construct(
        protected DepartmentRepository $departmentRepository
    ) {}

    /**
     * List departments with pagination, filters, sorting, search.
     */
    public function list(array $params)
    {
        $page = $params['page'] ?? 1;
        $limit = $params['per_page'] ?? 15;
        $filters = $params['filters'] ?? [];
        $sorts = $params['sorts'] ?? [];
        $search = $params['search'] ?? [];

        if (isset($params['no_paginate']) && $params['no_paginate']) {
            return $this->departmentRepository
                ->pushCriteria(new SortAndFilterDepartmentCriteria($filters, $sorts, $search))
                ->with(['jobTitles'])
                ->all();
        }

        return $this->departmentRepository
            ->pushCriteria(new SortAndFilterDepartmentCriteria($filters, $sorts, $search))
            ->with(['jobTitles'])
            ->paginate($limit);
    }

    /**
     * Get department detail by code.
     */
    public function showByCode(string $codeOrId)
    {
        $query = is_numeric($codeOrId) ? ['id' => (int) $codeOrId] : ['code' => $codeOrId];
        $department = $this->departmentRepository->findWhere($query)->first();

        if (!$department) {
            throw new BusinessException(ExceptionCode::DEPARTMENT_NOT_FOUND ?? 'DEPARTMENT_NOT_FOUND', 'Department not found', 404);
        }

        $department->load('jobTitles');

        return $department;
    }

    public function create(array $data)
    {
        $this->beginTransaction();
        try {
            $jobTitlesData = $data['job_titles'] ?? [];
            unset($data['job_titles']);

            $department = $this->departmentRepository->create($data);

            foreach ($jobTitlesData as $jobTitleItem) {
                $department->jobTitles()->create([
                    'name' => $jobTitleItem['name'],
                    'description' => $jobTitleItem['description'] ?? null,
                    'status' => 'ACTIVE',
                ]);
            }

            $department->load('jobTitles');
            $this->commitTransaction();

            return $department;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Update an existing department.
     */
    public function updateByCode(string $code, array $data)
    {
        $this->beginTransaction();
        try {
            $department = $this->departmentRepository->findWhere(['code' => $code])->first();

            if (!$department) {
                throw new BusinessException(ExceptionCode::DEPARTMENT_NOT_FOUND ?? 'DEPARTMENT_NOT_FOUND', 'Department not found', 404);
            }

            $jobTitlesData = $data['job_titles'] ?? [];
            unset($data['job_titles']);

            $this->departmentRepository->update($department->id, $data);

            // Sync job titles
            $keepJobTitleIds = [];
            foreach ($jobTitlesData as $jobTitleItem) {
                if (isset($jobTitleItem['id'])) {
                    // Update existing
                    $department->jobTitles()->where('id', $jobTitleItem['id'])->update([
                        'name' => $jobTitleItem['name'],
                        'description' => $jobTitleItem['description'] ?? null,
                    ]);
                    $keepJobTitleIds[] = $jobTitleItem['id'];
                } else {
                    // Create new
                    $newJobTitle = $department->jobTitles()->create([
                        'name' => $jobTitleItem['name'],
                        'description' => $jobTitleItem['description'] ?? null,
                        'status' => 'ACTIVE',
                    ]);
                    $keepJobTitleIds[] = $newJobTitle->id;
                }
            }

            // Delete ones that were removed
            $department->jobTitles()->whereNotIn('id', $keepJobTitleIds)->delete();

            $department = $this->departmentRepository->find($department->id);
            $department->load('jobTitles');

            $this->commitTransaction();

            return $department;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Delete department.
     */
    public function deleteByCode(string $code)
    {
        $this->beginTransaction();
        try {
            $department = $this->departmentRepository->findWhere(['code' => $code])->first();

            if (!$department) {
                throw new BusinessException(ExceptionCode::DEPARTMENT_NOT_FOUND ?? 'DEPARTMENT_NOT_FOUND', 'Department not found', 404);
            }

            $this->departmentRepository->deleteWhere(['id' => $department->id]);
            $this->commitTransaction();

            return true;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }
}
