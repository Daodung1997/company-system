<?php

namespace App\Services\Admin;

use App\Constants\Commons\App;
use App\Constants\Commons\CommonAuthConst;
use App\Constants\Commons\ExceptionCode;
use App\Constants\Commons\GenderConst;
use App\Constants\Master\Models\User\UserRoleConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Exceptions\BusinessException;
use App\Models\User;
use App\Repositories\Criteria\Master\User\SortAndFilterUserCriteria;
use App\Repositories\CustomerProfile\CustomerProfileRepository;
use App\Repositories\User\UserRepository;
use App\Services\Common\BaseAuthService;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminCustomerService extends BaseAuthService
{
    protected $repository;

    protected $profileRepository;

    public function __construct(
        UserRepository $userRepository,
        CustomerProfileRepository $profileRepository
    ) {
        $this->repository = $userRepository;
        $this->profileRepository = $profileRepository;
    }

    public function guard()
    {
        return auth()->guard(CommonAuthConst::GUARD_ADMIN);
    }

    protected function guardName(): string
    {
        return CommonAuthConst::GUARD_ADMIN;
    }

    /**
     * List customers with filters
     */
    public function list(array $params): LengthAwarePaginator
    {
        $filters = $params['filters'] ?? [];
        $sorts = $params['sorts'] ?? [];
        $search = $params['search'] ?? [];
        $limit = $params['limit'] ?? App::PER_PAGE;

        // Force filter by role = CUSTOMER
        $filters['role'] = UserRoleConst::CUSTOMER;

        return $this->repository->pushCriteria(
            new SortAndFilterUserCriteria($filters, $sorts, $search)
        )->with(['customerProfile'])->paginate($limit);
    }

    /**
     * Show customer detail
     *
     * @return User
     */
    public function show(int $id)
    {
        $user = $this->repository->with(['customerProfile'])->find($id);

        if (! $user || $user->role !== UserRoleConst::CUSTOMER) {
            throw new BusinessException(ExceptionCode::USER_NOT_FOUND, 'Customer not found', 404);
        }

        return $user;
    }

    /**
     * Update customer
     *
     * @return User
     */
    public function update(int $id, array $data)
    {
        $user = $this->show($id);

        $this->beginTransaction();
        try {
            // Update User Info
            $user->update([
                'name' => $data['name'] ?? $user->name,
                // 'phone' => user table doesn't have phone usually, profile has.
            ]);

            // Update Profile Info
            if ($user->customerProfile) {
                $user->customerProfile->update([
                    'phone' => $data['phone'] ?? $user->customerProfile->phone,
                    'address' => $data['address'] ?? $user->customerProfile->address,
                    'area_id' => $data['area_id'] ?? $user->customerProfile->area_id,
                    'avatar_code' => $data['avatar_code'] ?? $user->customerProfile->avatar_code,
                    'gender' => isset($data['gender']) ? GenderConst::toInteger($data['gender']) : $user->customerProfile->gender,
                    'birthday' => $data['dob'] ?? $user->customerProfile->birthday,
                ]);
            } else {
                // If profile missing, create one
                $this->profileRepository->create([
                    'user_id' => $user->id,
                    'phone' => $data['phone'] ?? null,
                    'address' => $data['address'] ?? null,
                    'area_id' => $data['area_id'] ?? null,
                    'avatar_code' => $data['avatar_code'] ?? null,
                    'gender' => isset($data['gender']) ? GenderConst::toInteger($data['gender']) : null,
                    'birthday' => $data['dob'] ?? null,
                ]);
            }

            $this->commitTransaction();

            return $user->refresh();
        } catch (Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Toggle Customer Status (Block/Unblock)
     *
     * @return User
     */
    public function toggleStatus(int $id, string $status, ?string $reason = null)
    {
        $user = $this->show($id);

        // Validate status transition
        if (! in_array($status, [UserStatusConst::ACTIVE, UserStatusConst::BLOCKED])) {
            throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Invalid status', 422);
        }

        $this->beginTransaction();
        try {
            $user->status = $status;
            $user->block_reason = ($status === UserStatusConst::BLOCKED) ? $reason : null;
            $user->save();
            $this->commitTransaction();

            return $user;
        } catch (Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }
}
