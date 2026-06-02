<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRelativeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRelativeRequest;
use App\Http\Resources\Employee\EmployeeRelativeResource;
use App\Services\Employee\EmployeeService;
use App\Supports\Facades\Response\Response;

class EmployeeRelativeController extends Controller
{
    public function __construct(protected EmployeeService $employeeService) {}

    /**
     * GET /api/employees/{id}/relatives - List relatives
     */
    public function index(int $id)
    {
        $relatives = $this->employeeService->getRelatives($id);

        return Response::success([
            'items' => EmployeeRelativeResource::collection($relatives),
        ]);
    }

    /**
     * POST /api/employees/{id}/relatives - Create relative
     */
    public function store(StoreEmployeeRelativeRequest $request, int $id)
    {
        $relative = $this->employeeService->createRelative($id, $request->validated());

        return Response::created((new EmployeeRelativeResource($relative))->resolve());
    }

    /**
     * PUT /api/employees/{id}/relatives/{relativeId} - Update relative
     */
    public function update(UpdateEmployeeRelativeRequest $request, int $id, int $relativeId)
    {
        $relative = $this->employeeService->updateRelative($id, $relativeId, $request->validated());

        return Response::success((new EmployeeRelativeResource($relative))->resolve());
    }

    /**
     * DELETE /api/employees/{id}/relatives/{relativeId} - Delete relative
     */
    public function destroy(int $id, int $relativeId)
    {
        $this->employeeService->deleteRelative($id, $relativeId);

        return Response::success(['message' => 'Relative has been deleted.']);
    }
}
