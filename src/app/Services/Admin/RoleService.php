<?php

namespace App\Services\Admin;

use App\Constants\Commons\ExceptionCode;
use App\Exceptions\BusinessException;
use App\Repositories\Admin\PermissionRepository;
use App\Repositories\Admin\RoleRepository;
use App\Repositories\Criteria\Role\SortAndFilterRoleCriteria;
use App\Services\AbstractService;

class RoleService extends AbstractService
{
    public function __construct(
        protected RoleRepository $roleRepository,
        protected PermissionRepository $permissionRepository
    ) {}

    public function listRoles(array $filters = [])
    {
        $paginator = $this->roleRepository
            ->pushCriteria(new SortAndFilterRoleCriteria($filters, $filters['sort'] ?? [], $filters['search'] ?? []))
            ->paginate($filters['limit'] ?? 20);

        $paginator->getCollection()->loadCount('users');

        return $paginator;
    }

    public function createRole(array $data)
    {
        $this->beginTransaction();
        try {
            $data['guard_name'] = 'admin';
            $role = $this->roleRepository->create([
                'name' => $data['name'],
                'guard_name' => 'admin',
            ]);

            if (isset($data['permission_ids']) && is_array($data['permission_ids'])) {
                $role->syncPermissions($data['permission_ids']);
            }

            $this->commitTransaction();

            return $role->load('permissions');
        } catch (\Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    public function getRoleDetail(int $id)
    {
        $role = $this->roleRepository->with(['permissions'])->find($id);
        if (! $role) {
            throw new BusinessException(ExceptionCode::ROLE_NOT_FOUND, 'Role not found', 404);
        }

        return $role;
    }

    public function updateRole(int $id, array $data)
    {
        $role = $this->roleRepository->find($id);
        if (! $role) {
            throw new BusinessException(ExceptionCode::ROLE_NOT_FOUND, 'Role not found', 404);
        }

        if ($role->name === 'super_admin') {
            throw new BusinessException(ExceptionCode::ROLE_SUPER_ADMIN_CANNOT_BE_MODIFIED, 'Cannot modify super_admin role', 400);
        }

        $this->beginTransaction();
        try {
            if (isset($data['name'])) {
                $this->roleRepository->update($id, [
                    'name' => $data['name'],
                ]);
            }

            if (isset($data['permission_ids'])) {
                $role->syncPermissions($data['permission_ids']);
            }

            $this->commitTransaction();

            return $role->load('permissions');
        } catch (\Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    public function deleteRole(int $id)
    {
        $role = $this->roleRepository->find($id);

        if (! $role) {
            throw new BusinessException(ExceptionCode::ROLE_NOT_FOUND, 'Role not found', 404);
        }

        if ($role->name === 'super_admin') {
            throw new BusinessException(ExceptionCode::ROLE_SUPER_ADMIN_CANNOT_BE_DELETED, 'Cannot delete super_admin role', 400);
        }

        $role->loadCount('users');
        if ($role->users_count > 0) {
            throw new BusinessException(ExceptionCode::ROLE_IN_USE, 'Cannot delete role which is assigned to users', 400);
        }

        $this->beginTransaction();
        try {
            $role->delete();
            $this->commitTransaction();

            return true;
        } catch (\Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    public function listPermissions(array $filters = [])
    {
        return $this->permissionRepository->getByColumn(['guard_name' => 'admin']);
    }
}
