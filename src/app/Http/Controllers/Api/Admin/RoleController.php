<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Role\StoreRoleRequest;
use App\Http\Requests\Admin\Role\UpdateRoleRequest;
use App\Http\Resources\Admin\PermissionResource;
use App\Http\Resources\Admin\RoleResource;
use App\Services\Admin\RoleService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roleService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $roles = $this->roleService->listRoles($request->all());

        return Response::pagination(
            RoleResource::collection($roles),
            $roles->total(),
            $roles->currentPage(),
            $roles->perPage()
        );
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roleService->createRole($request->validated());

        return Response::created(['role' => new RoleResource($role)]);
    }

    public function show(int $id): JsonResponse
    {
        $role = $this->roleService->getRoleDetail($id);

        return Response::success(['role' => new RoleResource($role)]);
    }

    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        $role = $this->roleService->updateRole($id, $request->validated());

        return Response::success(['role' => new RoleResource($role)]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->roleService->deleteRole($id);

        return Response::success([], 'Role deleted successfully');
    }

    public function getPermissions(Request $request): JsonResponse
    {
        $permissions = $this->roleService->listPermissions($request->all());

        return Response::success(['permissions' => PermissionResource::collection($permissions)]);
    }
}
