<?php

namespace App\Services\Wallet;

use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\User\UserRoleConst;
use App\Constants\Master\Models\WorkerProfile\WorkerProfileStatus;
use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionStatusConst;
use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionTypeConst;
use App\Exceptions\BusinessException;
use App\Models\User;
use App\Repositories\Criteria\Wallet\SortAndFilterWalletTransactionCriteria;
use App\Repositories\Wallet\WalletTransactionRepository;
use App\Repositories\WorkerProfile\WorkerProfileRepository;
use App\Services\AbstractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class WalletService extends AbstractService
{
    protected $walletTransactionRepository;

    protected $workerProfileRepository;

    public function __construct(
        WalletTransactionRepository $walletTransactionRepository,
        WorkerProfileRepository $workerProfileRepository
    ) {
        $this->walletTransactionRepository = $walletTransactionRepository;
        $this->workerProfileRepository = $workerProfileRepository;
    }

    /**
     * Get wallet balance for current worker.
     */
    public function getBalance(User $user): array
    {
        $this->assertWorkerCanAccessWallet($user);

        return [
            'available_balance' => $this->walletTransactionRepository->calculateAvailableBalance($user->id),
            'pending_balance' => $this->walletTransactionRepository->calculatePendingBalance($user->id),
            'total_earnings' => $this->walletTransactionRepository->getTotalEarnings($user->id),
            'total_withdrawn' => $this->walletTransactionRepository->getTotalWithdrawn($user->id),
        ];
    }

    /**
     * List transactions with pagination and filters.
     */
    public function listTransactions(Request $request, User $user)
    {
        $this->assertWorkerCanAccessWallet($user);

        $filters = array_filter(array_merge(
            $request->query('filters', []),
            [
                'worker_id' => $user->id,
                'type' => $request->query('type', $request->query('filters.type')),
                'date_from' => $request->query('date_from', $request->query('filters.date_from')),
                'date_to' => $request->query('date_to', $request->query('filters.date_to')),
            ]
        ), static fn ($value) => $value !== null && $value !== '');
        $sorts = $request->query('sorts', []);
        $search = $request->query('search', []);
        $limit = (int) $request->query(
            'per_page',
            $request->query('limit', App::make('config')->get('app.per_page', 20))
        );

        return $this->walletTransactionRepository->pushCriteria(
            new SortAndFilterWalletTransactionCriteria($filters, $sorts, $search)
        )->paginate($limit);
    }

    /**
     * Credit worker wallet with escrow (pending status).
     */
    public function creditWorkerEscrow($workerId, $amount, $description, $jobId, $releaseDays = 3)
    {
        return $this->walletTransactionRepository->createPendingEarning(
            $workerId,
            $jobId,
            $amount,
            $description,
            now()->addDays($releaseDays),
            'SYSTEM'
        );
    }

    /**
     * Credit worker wallet.
     */
    public function creditWorker($workerId, $amount, $description, $referenceId = null)
    {
        if ($referenceId) {
            return $this->walletTransactionRepository->releaseEarning(
                $workerId,
                $referenceId,
                $amount,
                $description,
                'SYSTEM'
            );
        }

        return $this->walletTransactionRepository->create([
            'worker_id' => $workerId,
            'amount' => $amount,
            'type' => WalletTransactionTypeConst::EARNING,
            'status' => WalletTransactionStatusConst::RELEASED,
            'description' => $description,
            'job_id' => null,
            'created_by' => 'SYSTEM',
            'updated_by' => 'SYSTEM',
        ]);
    }

    /**
     * Record cash earning (does not affect available balance but affects total earnings).
     */
    public function recordCashEarning($workerId, $amount, $description, $jobId)
    {
        return $this->walletTransactionRepository->recordCashEarning(
            $workerId,
            $jobId,
            $amount,
            $description,
            'SYSTEM'
        );
    }

    /**
     * Debit worker wallet (Withdrawal).
     */
    public function debitWorker($workerId, $amount, $description, $referenceId = null)
    {
        return $this->walletTransactionRepository->create([
            'worker_id' => $workerId,
            'amount' => $amount,
            'type' => WalletTransactionTypeConst::WITHDRAWAL,
            'status' => WalletTransactionStatusConst::COMPLETED,
            'description' => $description,
            'job_id' => null,
            'created_by' => 'SYSTEM',
            'updated_by' => 'SYSTEM',
        ]);
    }

    /**
     * Debit worker wallet for platform fee.
     */
    public function debitFee($workerId, $amount, $description, $referenceId = null)
    {
        return $this->walletTransactionRepository->create([
            'worker_id' => $workerId,
            'amount' => $amount,
            'type' => WalletTransactionTypeConst::FEE,
            'status' => WalletTransactionStatusConst::RELEASED,
            'description' => $description,
            'job_id' => $referenceId,
            'created_by' => 'SYSTEM',
            'updated_by' => 'SYSTEM',
        ]);
    }

    protected function assertWorkerCanAccessWallet(User $user): void
    {
        if ($user->role !== UserRoleConst::WORKER) {
            throw new BusinessException(ExceptionCode::PERMISSION_DENIED, 'Không có quyền truy cập', 403);
        }

        $profile = $this->workerProfileRepository->findWhere(['user_id' => $user->id])->first();

        if (! $profile) {
            throw new BusinessException(ExceptionCode::WORKER_PROFILE_NOT_FOUND, 'Worker profile not found', 404);
        }

        if ($profile->profile_status !== WorkerProfileStatus::APPROVED) {
            throw new BusinessException(ExceptionCode::WORKER_NOT_APPROVED, 'Chưa được duyệt', 403);
        }
    }
}
