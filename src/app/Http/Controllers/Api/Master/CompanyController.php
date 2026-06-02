<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreCompanyRequest;
use App\Http\Requests\Master\UpdateCompanyRequest;
use App\Http\Resources\Master\CompanyResource;
use App\Services\Company\CompanyService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function __construct(protected CompanyService $companyService) {}

    /**
     * GET /api/master/company - List companies
     */
    public function index(Request $request)
    {
        $params = [
            'page' => $request->get('page', 1),
            'per_page' => $request->get('per_page', 15),
            'filters' => $request->get('filters', []),
            'sorts' => $request->get('sorts', []),
            'search' => $request->get('search', []),
            'no_paginate' => $request->get('no_paginate', false) || $request->get('per_page') == -1,
        ];

        // Parse search query parameter if frontend passes "q" in query string directly
        if ($request->has('q')) {
            $params['search']['q'] = $request->get('q');
        }

        $result = $this->companyService->list($params);

        if ($params['no_paginate']) {
            return Response::success(CompanyResource::collection($result)->resolve());
        }

        return Response::pagination(
            CompanyResource::collection($result),
            $result->total(),
            $result->currentPage(),
            $result->perPage()
        );
    }

    /**
     * GET /api/master/company/{code} - Company detail
     */
    public function show(string $code)
    {
        $company = $this->companyService->showByCode($code);

        return Response::success((new CompanyResource($company))->resolve());
    }

    /**
     * POST /api/master/company/create - Create company
     */
    public function store(StoreCompanyRequest $request)
    {
        $company = $this->companyService->create($request->validated());

        return Response::created((new CompanyResource($company))->resolve());
    }

    /**
     * PUT /api/master/company/{code} - Update company
     */
    public function update(UpdateCompanyRequest $request, string $code)
    {
        $company = $this->companyService->updateByCode($code, $request->validated());

        return Response::success((new CompanyResource($company))->resolve());
    }

    /**
     * DELETE /api/master/company/{code} - Delete company
     */
    public function destroy(string $code)
    {
        $this->companyService->deleteByCode($code);

        return Response::success(['message' => 'Company has been deactivated.']);
    }
}
