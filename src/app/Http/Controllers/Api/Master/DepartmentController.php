<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreDepartmentRequest;
use App\Http\Requests\Master\UpdateDepartmentRequest;
use App\Http\Resources\Master\DepartmentResource;
use App\Services\Department\DepartmentService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct(protected DepartmentService $departmentService) {}

    /**
     * GET /api/master/department - List departments
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

        $result = $this->departmentService->list($params);

        if ($params['no_paginate']) {
            return Response::success(DepartmentResource::collection($result)->resolve());
        }

        return Response::pagination(
            DepartmentResource::collection($result),
            $result->total(),
            $result->currentPage(),
            $result->perPage()
        );
    }

    /**
     * GET /api/master/department/{code} - Department detail
     */
    public function show(string $code)
    {
        $department = $this->departmentService->showByCode($code);

        return Response::success((new DepartmentResource($department))->resolve());
    }

    /**
     * POST /api/master/department/create - Create department
     */
    public function store(StoreDepartmentRequest $request)
    {
        $department = $this->departmentService->create($request->validated());

        return Response::created((new DepartmentResource($department))->resolve());
    }

    /**
     * PUT /api/master/department/{code} - Update department
     */
    public function update(UpdateDepartmentRequest $request, string $code)
    {
        $department = $this->departmentService->updateByCode($code, $request->validated());

        return Response::success((new DepartmentResource($department))->resolve());
    }

    /**
     * DELETE /api/master/department/{code} - Delete department
     */
    public function destroy(string $code)
    {
        $this->departmentService->deleteByCode($code);

        return Response::success(['message' => 'Department has been deleted.']);
    }
}
