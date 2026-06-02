<?php

namespace App\Services\Wallet;

use App\Constants\Commons\ExceptionCode;
use App\Exceptions\BusinessException;
use App\Repositories\Wallet\BankAccountRepository;
use App\Repositories\Wallet\WithdrawalRepository;
use App\Services\AbstractService;

class BankAccountService extends AbstractService
{
    protected $bankAccountRepository;

    protected $withdrawalRepository;

    public function __construct(
        BankAccountRepository $bankAccountRepository,
        WithdrawalRepository $withdrawalRepository
    ) {
        $this->bankAccountRepository = $bankAccountRepository;
        $this->withdrawalRepository = $withdrawalRepository;
    }

    /**
     * List all bank accounts for current worker
     */
    public function list(\Illuminate\Http\Request $request, int $userId)
    {
        $filters = $request->query('filters', []);
        $sorts = $request->query('sorts', []);
        $search = $request->query('search', []);

        // Add user_id filter
        $filters['user_id'] = $userId;

        return $this->bankAccountRepository->pushCriteria(
            new \App\Repositories\Criteria\Wallet\SortAndFilterBankAccountCriteria($filters, $sorts, $search)
        )->all();
    }

    /**
     * Create bank account
     */
    public function create(array $data, int $userId)
    {
        // Check duplicate account number
        $existing = $this->bankAccountRepository->findWhere([
            'user_id' => $userId,
            'account_number' => $data['account_number'],
        ])->first();

        if ($existing) {
            throw new BusinessException(
                ExceptionCode::DUPLICATE_BANK_ACCOUNT,
                'Số tài khoản đã tồn tại',
                422
            );
        }

        $this->beginTransaction();
        try {
            // If this is the first account or is_default is true, set as default
            $count = $this->bankAccountRepository->countByUser($userId);
            $isDefault = $count === 0 || ($data['is_default'] ?? false);

            $bankAccount = $this->bankAccountRepository->create([
                'user_id' => $userId,
                'bank_name' => $data['bank_name'],
                'account_number' => $data['account_number'],
                'account_name' => $data['account_name'],
                'branch' => $data['branch'] ?? null,
                'is_default' => $isDefault,
            ]);

            // If set as default, unset others
            if ($isDefault) {
                $this->bankAccountRepository->setAsDefault($bankAccount->id, $userId);
            }

            $this->commitTransaction();

            return $bankAccount;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Update bank account
     */
    public function update(int $id, array $data, int $userId)
    {
        $bankAccount = $this->bankAccountRepository->find($id);

        if (! $bankAccount || $bankAccount->user_id != $userId) {
            throw new BusinessException(
                ExceptionCode::BANK_ACCOUNT_NOT_FOUND,
                'Không tìm thấy',
                404
            );
        }

        // Check if there's pending withdrawal
        if ($this->withdrawalRepository->hasPendingWithdrawal($userId)) {
            throw new BusinessException(
                ExceptionCode::CANNOT_MODIFY_WITH_PENDING_WITHDRAWAL,
                'Không thể cập nhật khi đang có yêu cầu rút tiền',
                409
            );
        }

        $this->beginTransaction();
        try {
            $this->bankAccountRepository->update($id, [
                'bank_name' => $data['bank_name'],
                'account_number' => $data['account_number'],
                'account_name' => $data['account_name'],
                'branch' => $data['branch'] ?? null,
                'is_default' => $data['is_default'] ?? $bankAccount->is_default,
            ]);

            // If set as default, unset others
            if ($data['is_default'] ?? false) {
                $this->bankAccountRepository->setAsDefault($id, $userId);
            }

            $this->commitTransaction();

            return $this->bankAccountRepository->find($id);
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Delete bank account
     */
    public function delete(int $id, int $userId)
    {
        $bankAccount = $this->bankAccountRepository->find($id);

        if (! $bankAccount || $bankAccount->user_id != $userId) {
            throw new BusinessException(
                ExceptionCode::BANK_ACCOUNT_NOT_FOUND,
                'Không tìm thấy',
                404
            );
        }

        // Check if there's pending withdrawal
        if ($this->withdrawalRepository->hasPendingWithdrawal($userId)) {
            throw new BusinessException(
                ExceptionCode::CANNOT_MODIFY_WITH_PENDING_WITHDRAWAL,
                'Không thể xoá khi đang có yêu cầu rút tiền',
                409
            );
        }

        // Check if this is the last account
        $count = $this->bankAccountRepository->countByUser($userId);
        if ($count <= 1) {
            throw new BusinessException(
                ExceptionCode::MUST_HAVE_ONE_BANK_ACCOUNT,
                'Phải có ít nhất 1 tài khoản để rút tiền',
                409
            );
        }

        $this->beginTransaction();
        try {
            $bankAccount->delete();
            $this->commitTransaction();
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }
}
