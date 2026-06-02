<?php

namespace App\Services\Wallet;

use App\Constants\Transaction\Models\Withdrawal\WithdrawalStatusConst;
use App\Models\Withdrawal;
use App\Repositories\Wallet\WalletTransactionRepository;
use App\Repositories\Wallet\WithdrawalLogRepository;
use App\Repositories\Wallet\WithdrawalRepository;
use App\Services\AbstractService;
use App\Services\User\NotificationService;

class WithdrawalProcessingService extends AbstractService
{
    public function __construct(
        protected WithdrawalRepository $withdrawalRepository,
        protected WalletTransactionRepository $walletTransactionRepository,
        protected WithdrawalLogRepository $withdrawalLogRepository,
        protected WithdrawalGatewayService $withdrawalGatewayService,
        protected NotificationService $notificationService
    ) {}

    public function process(int $withdrawalId): void
    {
        $withdrawal = $this->withdrawalRepository->with(['bankAccount'])->find($withdrawalId);

        if (! $withdrawal || $withdrawal->status !== WithdrawalStatusConst::REQUESTED) {
            return;
        }

        $this->beginTransaction();

        try {
            $this->withdrawalRepository->update($withdrawal->id, [
                'status' => WithdrawalStatusConst::PROCESSING,
            ]);

            $this->createLog($withdrawal->id, 'payout_processing_started', WithdrawalStatusConst::PROCESSING, [
                'bank_account_id' => $withdrawal->bank_account_id,
                'amount' => (float) $withdrawal->amount,
            ]);

            $result = $this->withdrawalGatewayService->transfer($withdrawal);

            if (($result['success'] ?? false) === true) {
                $this->walletTransactionRepository->completeWithdrawal($withdrawal->id, 'SYSTEM');

                $this->withdrawalRepository->update($withdrawal->id, [
                    'status' => WithdrawalStatusConst::COMPLETED,
                    'processed_at' => now(),
                    'gateway_reference' => $result['gateway_reference'] ?? null,
                    'gateway_response' => $result['response'] ?? null,
                    'failure_reason' => null,
                ]);

                $this->createLog($withdrawal->id, 'payout_completed', WithdrawalStatusConst::COMPLETED, [
                    'gateway_reference' => $result['gateway_reference'] ?? null,
                    'response' => $result['response'] ?? null,
                ]);

                // Send Success Notification
                try {
                    $bankName = $withdrawal->bankAccount->bank_name ?? 'N/A';
                    $accountNumber = $withdrawal->bankAccount->account_number ?? '';
                    $lastDigits = strlen($accountNumber) > 4 ? substr($accountNumber, -4) : $accountNumber;

                    $this->notificationService->sendNotification(
                        $withdrawal->worker_id,
                        \App\Constants\Master\Models\Notification\NotificationTypeConst::WITHDRAWAL_SUCCESS,
                        __('notification.withdrawal_success.title'),
                        __('notification.withdrawal_success.body', [
                            'withdrawal_code' => $withdrawal->code,
                            'amount' => number_format($withdrawal->amount),
                        ]),
                        [
                            'withdrawal_code' => $withdrawal->code,
                            'amount' => (float) $withdrawal->amount,
                            'bank_name' => $bankName,
                            'card_last_digits' => $lastDigits
                        ]
                    );
                } catch (\Throwable $ne) {
                    \Illuminate\Support\Facades\Log::error('Withdrawal Success Notification Error: ' . $ne->getMessage());
                }

                $this->commitTransaction();

                return;
            }

            $this->markFailed(
                $withdrawal,
                $result['failure_reason'] ?? 'Payout failed',
                $result['gateway_reference'] ?? null,
                $result['response'] ?? null
            );

            $this->commitTransaction();
        } catch (\Throwable $e) {
            $this->markFailed(
                $withdrawal,
                $e->getMessage(),
                null,
                ['exception' => $e->getMessage()]
            );

            $this->commitTransaction();
        }
    }

    protected function markFailed(
        Withdrawal $withdrawal,
        string $failureReason,
        ?string $gatewayReference,
        mixed $gatewayResponse
    ): void {
        $this->walletTransactionRepository->failWithdrawal($withdrawal->id, 'SYSTEM');

        $this->withdrawalRepository->update($withdrawal->id, [
            'status' => WithdrawalStatusConst::FAILED,
            'processed_at' => now(),
            'failure_reason' => $failureReason,
            'gateway_reference' => $gatewayReference,
            'gateway_response' => $gatewayResponse,
        ]);

        $this->createLog($withdrawal->id, 'payout_failed', WithdrawalStatusConst::FAILED, [
            'failure_reason' => $failureReason,
            'gateway_reference' => $gatewayReference,
            'response' => $gatewayResponse,
        ]);

        // Send Failed Notification
        try {
            $this->notificationService->sendNotification(
                $withdrawal->worker_id,
                \App\Constants\Master\Models\Notification\NotificationTypeConst::WITHDRAWAL_FAILED,
                __('notification.withdrawal_failed.title'),
                __('notification.withdrawal_failed.body', [
                    'withdrawal_code' => $withdrawal->code,
                    'reason' => $failureReason,
                ]),
                [
                    'withdrawal_code' => $withdrawal->code,
                    'reason' => $failureReason
                ]
            );
        } catch (\Throwable $ne) {
            \Illuminate\Support\Facades\Log::error('Withdrawal Failed Notification Error: ' . $ne->getMessage());
        }
    }

    protected function createLog(int $withdrawalId, string $event, string $status, ?array $payload = null): void
    {
        $this->withdrawalLogRepository->create([
            'withdrawal_id' => $withdrawalId,
            'event' => $event,
            'status' => $status,
            'payload' => $payload,
            'created_by' => 'SYSTEM',
            'updated_by' => 'SYSTEM',
        ]);
    }
}
