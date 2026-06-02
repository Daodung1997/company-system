<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Resources\Employee\EmployeeDetailResource;
use App\Http\Resources\Employee\EmployeeListResource;
use App\Services\Employee\EmployeeService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(protected EmployeeService $employeeService) {}

    /**
     * GET /api/employees - List employees with pagination
     */
    public function index(Request $request)
    {
        $params = [
            'page' => $request->get('page', 1),
            'per_page' => $request->get('per_page', 15),
            'filters' => $request->get('filters', []),
            'sorts' => $request->get('sorts', []),
            'search' => $request->get('search', []),
        ];

        $result = $this->employeeService->list($params);

        return Response::pagination(
            EmployeeListResource::collection($result),
            $result->total(),
            $result->currentPage(),
            $result->perPage()
        );
    }

    /**
     * GET /api/employees/{id} - Employee detail
     */
    public function show(int $id)
    {
        $employee = $this->employeeService->show($id);

        return Response::success((new EmployeeDetailResource($employee))->resolve());
    }

    /**
     * POST /api/employees - Create employee
     */
    public function store(StoreEmployeeRequest $request)
    {
        $employee = $this->employeeService->create($request->validated());

        return Response::created((new EmployeeDetailResource($employee))->resolve());
    }

    /**
     * PUT /api/employees/{id} - Update employee
     */
    public function update(UpdateEmployeeRequest $request, int $id)
    {
        $employee = $this->employeeService->update($id, $request->validated());

        return Response::success((new EmployeeDetailResource($employee))->resolve());
    }

    public function destroy(int $id)
    {
        $this->employeeService->delete($id);

        return Response::success(['message' => 'Employee has been deactivated.']);
    }

    /**
     * POST /api/employees/{id}/documents - Upload a personal document for this employee
     */
    public function uploadDocument(Request $request, int $id)
    {
        $request->validate([
            'file' => 'required|file',
            'title' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');
        $document = $this->employeeService->uploadDocument($id, $file, $request->get('title'));

        return Response::success((new \App\Http\Resources\Document\DocumentResource($document))->resolve());
    }
}
