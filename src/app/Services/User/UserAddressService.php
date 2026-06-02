<?php

namespace App\Services\User;

use App\Constants\Commons\ExceptionCode;
use App\Exceptions\BusinessException;
use App\Models\UserAddress;
use App\Repositories\Criteria\User\UserAddress\SortAndFilterUserAddressCriteria;
use App\Repositories\UserAddress\UserAddressRepository;
use App\Services\AbstractService;

class UserAddressService extends AbstractService
{
    protected $repository;

    private const MAX_ADDRESSES = 10;

    public function __construct(UserAddressRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * List all addresses for a user, default first.
     */
    public function list(int $userId)
    {
        return $this->repository
            ->pushCriteria(new SortAndFilterUserAddressCriteria)
            ->findWhere(['user_id' => $userId])
            ->all();
    }

    /**
     * Create a new address.
     */
    public function create(int $userId, array $data): UserAddress
    {
        $this->beginTransaction();
        try {
            // Check max limit
            $count = $this->repository->findWhere(['user_id' => $userId])->count();
            if ($count >= self::MAX_ADDRESSES) {
                throw new BusinessException(
                    ExceptionCode::MAX_ADDRESS_LIMIT,
                    'Maximum address limit reached (max '.self::MAX_ADDRESSES.')',
                    422
                );
            }

            $data['user_id'] = $userId;

            // Auto set default if first address
            if ($count === 0) {
                $data['is_default'] = true;
            }

            // If setting as default, reset others
            if (! empty($data['is_default'])) {
                $this->resetDefaultAddresses($userId);
            }

            $address = $this->repository->create($data);

            $this->commitTransaction();

            return $address->load(['area', 'ward']);
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Update an existing address (IDOR-safe).
     */
    public function update(int $userId, int $addressId, array $data): UserAddress
    {
        $this->beginTransaction();
        try {
            $address = $this->findUserAddress($userId, $addressId);

            // If setting as default, reset others first
            if (! empty($data['is_default'])) {
                $this->resetDefaultAddresses($userId);
            }

            $this->repository->update($address->id, $data);

            $this->commitTransaction();

            return $address->fresh(['area', 'ward']);
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Soft delete an address (IDOR-safe).
     */
    public function delete(int $userId, int $addressId): void
    {
        $this->beginTransaction();
        try {
            $address = $this->findUserAddress($userId, $addressId);

            // Cannot delete default if other addresses exist
            if ($address->is_default) {
                $otherCount = $this->repository
                    ->findWhere(['user_id' => $userId])
                    ->all()
                    ->where('id', '!=', $addressId)
                    ->count();

                if ($otherCount > 0) {
                    throw new BusinessException(
                        ExceptionCode::CANNOT_DELETE_DEFAULT_ADDRESS,
                        'Cannot delete default address. Set another address as default first.',
                        422
                    );
                }
            }

            $address->delete();

            $this->commitTransaction();
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Find address scoped to user (IDOR check).
     */
    private function findUserAddress(int $userId, int $addressId): UserAddress
    {
        $address = $this->repository->findWhere([
            'id' => $addressId,
            'user_id' => $userId,
        ])->first();

        if (! $address) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Resource not found', 404);
        }

        return $address;
    }

    /**
     * Reset all user addresses to non-default.
     */
    private function resetDefaultAddresses(int $userId): void
    {
        $this->repository
            ->findWhere(['user_id' => $userId, 'is_default' => true])
            ->modelUpdate(['is_default' => false]);
    }
}
