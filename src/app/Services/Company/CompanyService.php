<?php

namespace App\Services\Company;

use App\Constants\Commons\ExceptionCode;
use App\Exceptions\BusinessException;
use App\Repositories\Company\CompanyRepository;
use App\Repositories\Criteria\Company\SortAndFilterCompanyCriteria;
use App\Services\AbstractService;

class CompanyService extends AbstractService
{
    public function __construct(
        protected CompanyRepository $companyRepository
    ) {}

    /**
     * List companies with pagination, filters, sorting, search.
     */
    public function list(array $params)
    {
        $page = $params['page'] ?? 1;
        $limit = $params['per_page'] ?? 15;
        $filters = $params['filters'] ?? [];
        $sorts = $params['sorts'] ?? [];
        $search = $params['search'] ?? [];

        if (isset($params['no_paginate']) && $params['no_paginate']) {
            return $this->companyRepository
                ->pushCriteria(new SortAndFilterCompanyCriteria($filters, $sorts, $search))
                ->all();
        }

        return $this->companyRepository
            ->pushCriteria(new SortAndFilterCompanyCriteria($filters, $sorts, $search))
            ->paginate($limit);
    }

    /**
     * Get company detail by code.
     */
    public function showByCode(string $code)
    {
        $company = $this->companyRepository->findWhere(['code' => $code])->first();

        if (!$company) {
            throw new BusinessException(ExceptionCode::COMPANY_NOT_FOUND ?? 'COMPANY_NOT_FOUND', 'Company not found', 404);
        }

        return $company;
    }

    /**
     * Create a new company.
     */
    public function create(array $data)
    {
        $this->beginTransaction();
        try {
            if (isset($data['status'])) {
                $data['status'] = strtoupper($data['status']);
            }
            $company = $this->companyRepository->create($data);
            $this->commitTransaction();

            return $company;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Update an existing company.
     */
    public function updateByCode(string $code, array $data)
    {
        $this->beginTransaction();
        try {
            $company = $this->companyRepository->findWhere(['code' => $code])->first();

            if (!$company) {
                throw new BusinessException(ExceptionCode::COMPANY_NOT_FOUND ?? 'COMPANY_NOT_FOUND', 'Company not found', 404);
            }

            if (isset($data['status'])) {
                $data['status'] = strtoupper($data['status']);
            }
            $this->companyRepository->update($company->id, $data);
            $company = $this->companyRepository->find($company->id);

            $this->commitTransaction();

            return $company;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Soft delete company.
     */
    public function deleteByCode(string $code)
    {
        $this->beginTransaction();
        try {
            $company = $this->companyRepository->findWhere(['code' => $code])->first();

            if (!$company) {
                throw new BusinessException(ExceptionCode::COMPANY_NOT_FOUND ?? 'COMPANY_NOT_FOUND', 'Company not found', 404);
            }

            $this->companyRepository->update($company->id, ['status' => 'INACTIVE']);
            $this->commitTransaction();

            return true;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }
}
