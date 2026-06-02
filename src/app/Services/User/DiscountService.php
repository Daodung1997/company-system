<?php

namespace App\Services\User;

use App\Constants\Commons\CommonStatusConst;
use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\Discount\DiscountTypeConst;
use App\Exceptions\BusinessException;
use App\Models\Discount;
use App\Repositories\Criteria\Discount\SortAndFilterDiscountCriteria;
use App\Repositories\Discount\DiscountRepository;
use App\Repositories\Job\JobRepository;
use App\Services\AbstractService;
use Carbon\Carbon;

class DiscountService extends AbstractService
{
    protected $repository;

    protected $jobRepository;

    public function __construct(DiscountRepository $repository, JobRepository $jobRepository)
    {
        $this->repository = $repository;
        $this->jobRepository = $jobRepository;
    }

    /**
     * List discounts for Admin.
     */
    public function listAdmin(array $filters = [], array $sorts = [], array $search = [], int $limit = 15)
    {
        return $this->repository
            ->pushCriteria(new SortAndFilterDiscountCriteria($filters, $sorts, $search))
            ->paginate($limit);
    }

    /**
     * Create a discount.
     */
    public function createAdmin(array $data): Discount
    {
        $this->beginTransaction();
        try {
            $data['code'] = strtoupper($data['code']);
            $discount = $this->repository->create($data);
            $this->commitTransaction();

            return $discount;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Show detail of a discount.
     */
    public function showAdmin(int $id): Discount
    {
        $discount = $this->repository->find($id);
        if (! $discount) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Discount not found', 404);
        }

        return $discount;
    }

    /**
     * Update an existing discount.
     */
    public function updateAdmin(int $id, array $data): Discount
    {
        $this->beginTransaction();
        try {
            $discount = $this->showAdmin($id);

            // Constraint: total_quantity must be >= used_quantity
            if (isset($data['total_quantity']) && $data['total_quantity'] < $discount->used_quantity) {
                throw new BusinessException(
                    ExceptionCode::INVALID_VOUCHER,
                    'Total quantity cannot be less than used quantity ('.$discount->used_quantity.')',
                    422
                );
            }

            // Only allow safe update fields: title, total_quantity, end_date, status, note
            $updateData = array_intersect_key($data, array_flip([
                'title',
                'total_quantity',
                'end_date',
                'status',
                'note',
            ]));

            $this->repository->update($discount->id, $updateData);

            $this->commitTransaction();

            return $discount->fresh();
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Toggle status.
     */
    public function toggleStatusAdmin(int $id): Discount
    {
        $this->beginTransaction();
        try {
            $discount = $this->showAdmin($id);
            $newStatus = $discount->status == CommonStatusConst::ACTIVE ? CommonStatusConst::INACTIVE : CommonStatusConst::ACTIVE;

            $this->repository->update($discount->id, ['status' => $newStatus]);

            $this->commitTransaction();

            return $discount->fresh();
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Validate a voucher against 5 business rules.
     */
    public function validateVoucher(string $code, ?float $price = null, ?int $userId = null): Discount
    {
        $code = strtoupper($code);
        $discount = $this->repository->findWhere(['code' => $code])->first();

        // 1. Check existence and status ACTIVE
        if (! $discount || $discount->status != CommonStatusConst::ACTIVE) {
            throw new BusinessException(
                ExceptionCode::INVALID_VOUCHER,
                'Voucher code is invalid or inactive',
                422
            );
        }

        $now = Carbon::now();
        // 2. Check validity dates
        if ($now->lt($discount->start_date) || $now->gt($discount->end_date)) {
            throw new BusinessException(
                ExceptionCode::VOUCHER_EXPIRED,
                'Voucher code has expired or is not yet active',
                422
            );
        }

        // 3. Check total usage limits
        if ($discount->total_quantity !== null && $discount->used_quantity >= $discount->total_quantity) {
            throw new BusinessException(
                ExceptionCode::VOUCHER_LIMIT_REACHED,
                'Voucher usage limit has been reached',
                422
            );
        }

        // 4. Check minimum order amount
        if ($price !== null && $price < $discount->min_order_amount) {
            throw new BusinessException(
                ExceptionCode::VOUCHER_MIN_ORDER_AMOUNT_NOT_MET,
                'Order amount does not meet the minimum requirement of '.number_format($discount->min_order_amount),
                422
            );
        }

        // 5. Check user-specific usage limit
        if ($userId !== null) {
            $usedCount = $this->jobRepository->findWhere([
                'customer_id' => $userId,
                'discount_id' => $discount->id,
            ])->count();

            if ($usedCount >= $discount->max_uses_per_user) {
                throw new BusinessException(
                    ExceptionCode::VOUCHER_USER_LIMIT_REACHED,
                    'You have already reached the usage limit for this voucher',
                    422
                );
            }
        }

        return $discount;
    }

    /**
     * Calculate discount amount.
     */
    public function calculateDiscount(Discount $discount, float $price): float
    {
        $discountAmount = 0.0;

        if ($discount->discount_type === DiscountTypeConst::PERCENTAGE) {
            $discountAmount = $price * ($discount->discount_value / 100);
            if ($discount->max_discount_amount !== null && $discountAmount > $discount->max_discount_amount) {
                $discountAmount = $discount->max_discount_amount;
            }
        } elseif ($discount->discount_type === DiscountTypeConst::FIXED_AMOUNT) {
            $discountAmount = $discount->discount_value;
        }

        return min($discountAmount, $price);
    }
}
