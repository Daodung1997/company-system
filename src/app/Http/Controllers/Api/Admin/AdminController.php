<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Admin\ChangeAdminStatusRequest;
use App\Http\Requests\Admin\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\Admin\UpdateAdminRequest;
use App\Http\Resources\Admin\AdminResource;
use App\Services\Admin\AdminService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        protected AdminService $adminService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $admins = $this->adminService->listAdmins($request->all());

        return Response::pagination(
            AdminResource::collection($admins),
            $admins->total(),
            $admins->currentPage(),
            $admins->perPage()
        );
    }

    public function store(StoreAdminRequest $request): JsonResponse
    {
        $admin = $this->adminService->createAdmin($request->validated());

        return Response::created(['admin' => new AdminResource($admin)]);
    }

    public function show(int $id): JsonResponse
    {
        $admin = $this->adminService->getAdminDetail($id);

        return Response::success(['admin' => new AdminResource($admin)]);
    }

    public function update(UpdateAdminRequest $request, int $id): JsonResponse
    {
        $admin = $this->adminService->updateAdmin($id, $request->validated());

        return Response::success(['admin' => new AdminResource($admin)]);
    }

    public function toggleStatus(ChangeAdminStatusRequest $request, int $id): JsonResponse
    {
        $admin = $this->adminService->changeStatus($id, $request->input('status'), $request->user()->id);

        return Response::success(['admin' => new AdminResource($admin)]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->adminService->deleteAdmin($id, $request->user()->id);

        return Response::success([], 'Admin deleted successfully');
    }
}
