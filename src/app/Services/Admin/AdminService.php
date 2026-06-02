<?php

namespace App\Services\Admin;

use App\Constants\Commons\ExceptionCode;
use App\Exceptions\BusinessException;
use App\Repositories\Admin\AdminRepository;
use App\Repositories\Criteria\Admin\SortAndFilterAdminCriteria;
use App\Services\AbstractService;
use Illuminate\Support\Facades\Hash;

class AdminService extends AbstractService
{
    public function __construct(
        protected AdminRepository $adminRepository
    ) {}

    public function listAdmins(array $filters = [])
    {
        return $this->adminRepository
            ->pushCriteria(new SortAndFilterAdminCriteria($filters, $filters['sort'] ?? [], $filters['search'] ?? []))
            ->paginate($filters['limit'] ?? 20);
    }

    public function createAdmin(array $data)
    {
        $this->beginTransaction();
        try {
            $data['password'] = Hash::make($data['password']);

            $roleIds = $data['role_ids'] ?? [];
            unset($data['role_ids']);

            $admin = $this->adminRepository->create($data);

            if (! empty($roleIds)) {
                $admin->syncRoles($roleIds);
            }

            $this->commitTransaction();

            return $admin->load('roles');
        } catch (\Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    public function getAdminDetail(int $id)
    {
        $admin = $this->adminRepository->with(['roles.permissions'])->find($id);
        if (! $admin) {
            throw new BusinessException(ExceptionCode::ADMIN_NOT_FOUND, 'Admin not found', 404);
        }

        return $admin;
    }

    public function updateAdmin(int $id, array $data)
    {
        $admin = $this->adminRepository->find($id);
        if (! $admin) {
            throw new BusinessException(ExceptionCode::ADMIN_NOT_FOUND, 'Admin not found', 404);
        }

        $this->beginTransaction();
        try {
            // Unset password in update data to prevent unintentional changes
            unset($data['password']);

            $roleIds = $data['role_ids'] ?? null;
            if (array_key_exists('role_ids', $data)) {
                unset($data['role_ids']);
            }

            $this->adminRepository->update($id, $data);

            if ($roleIds !== null) {
                $admin->syncRoles($roleIds);
            }

            $this->commitTransaction();

            return $admin->load('roles');
        } catch (\Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    public function changeStatus(int $id, string $status, int $currentAdminId)
    {
        $admin = $this->adminRepository->find($id);

        if (! $admin) {
            throw new BusinessException(ExceptionCode::ADMIN_NOT_FOUND, 'Admin not found', 404);
        }

        if ($id === $currentAdminId) {
            throw new BusinessException(ExceptionCode::CANNOT_DEACTIVATE_SELF, 'Cannot deactivate self', 400);
        }

        $this->beginTransaction();
        try {
            $this->adminRepository->update($id, [
                'status' => $status,
            ]);
            $this->commitTransaction();

            return $admin->refresh();
        } catch (\Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    public function deleteAdmin(int $id, int $currentAdminId)
    {
        $admin = $this->adminRepository->find($id);

        if (! $admin) {
            throw new BusinessException(ExceptionCode::ADMIN_NOT_FOUND, 'Admin not found', 404);
        }

        if ($id === $currentAdminId) {
            throw new BusinessException(ExceptionCode::CANNOT_DELETE_SELF, 'Cannot delete self', 400);
        }

        $this->beginTransaction();
        try {
            $admin->delete();
            $this->commitTransaction();

            return true;
        } catch (\Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }
}
