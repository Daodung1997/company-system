<?php

namespace App\Services\Transaction;

use App\Constants\Commons\ExceptionCode;
use App\Constants\Transaction\Models\Transaction\TaxRateTypeConst;
use App\Exceptions\BusinessException;
use App\Models\Transaction;
use App\Models\Document;
use App\Repositories\Transaction\TransactionRepository;
use App\Repositories\Document\DocumentRepository;
use App\Services\AbstractService;
use Illuminate\Support\Facades\Log;

class TransactionService extends AbstractService
{
    public function __construct(
        protected TransactionRepository $transactionRepository,
        protected DocumentRepository $documentRepository
    ) {}

    /**
     * Get transactions list under the user's company with filters.
     */
    public function list(array $filters)
    {
        $employee = auth('api')->user();
        if (!$employee) {
            throw new BusinessException(ExceptionCode::UNAUTHENTICATED, 'Unauthenticated', 401);
        }

        return $this->transactionRepository->getTransactions($filters);
    }

    /**
     * Get a specific transaction.
     */
    public function show(int $id)
    {
        $employee = auth('api')->user();
        if (!$employee) {
            throw new BusinessException(ExceptionCode::UNAUTHENTICATED, 'Unauthenticated', 401);
        }

        $transaction = $this->transactionRepository->with(['documents'])->find($id);

        if (!$transaction) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Giao dịch không tồn tại', 404);
        }

        return $transaction;
    }

    /**
     * Create a new transaction.
     */
    public function create(array $data)
    {
        $currentUser = auth('api')->user();
        if (!$currentUser) {
            throw new BusinessException(ExceptionCode::UNAUTHENTICATED, 'Unauthenticated', 401);
        }

        $data['created_by'] = $currentUser->full_name;

        // Perform auto-calculations for tax and net amounts if needed
        $data = $this->calculateTaxAndAmounts($data);

        $this->beginTransaction();
        try {
            // 1. Create the transaction
            $transaction = $this->transactionRepository->create($data);

            // 2. Attach documents if provided
            if (!empty($data['document_ids'])) {
                foreach ($data['document_ids'] as $docId) {
                    $document = $this->documentRepository->find($docId);
                    if ($document) {
                        $this->documentRepository->update($docId, [
                            'transaction_id' => $transaction->id,
                            'documentable_id' => $transaction->id,
                            'documentable_type' => Transaction::class,
                            'status' => 'in_use'
                        ]);
                    }
                }
            }

            $this->commitTransaction();

            return $this->transactionRepository->with(['documents'])->find($transaction->id);
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            Log::error('Error creating transaction: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing transaction.
     */
    public function update(int $id, array $data)
    {
        $currentUser = auth('api')->user();
        if (!$currentUser) {
            throw new BusinessException(ExceptionCode::UNAUTHENTICATED, 'Unauthenticated', 401);
        }

        $transaction = $this->transactionRepository->find($id);
        if (!$transaction) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Giao dịch không tồn tại', 404);
        }

        $data['updated_by'] = $currentUser->full_name;

        // Re-calculate amounts
        $data = $this->calculateTaxAndAmounts($data, $transaction);

        $this->beginTransaction();
        try {
            // 1. Update the transaction
            $this->transactionRepository->update($id, $data);

            // 2. Update documents if array is provided (even if empty)
            if (array_key_exists('document_ids', $data)) {
                // Detach all existing documents from this transaction
                Document::where('transaction_id', $id)->update([
                    'transaction_id' => null,
                    'documentable_id' => null,
                    'documentable_type' => null,
                ]);

                if (!empty($data['document_ids'])) {
                    foreach ($data['document_ids'] as $docId) {
                        $document = $this->documentRepository->find($docId);
                        if ($document) {
                            $this->documentRepository->update($docId, [
                                'transaction_id' => $id,
                                'documentable_id' => $id,
                                'documentable_type' => Transaction::class,
                                'status' => 'in_use'
                            ]);
                        }
                    }
                }
            }

            $this->commitTransaction();

            return $this->transactionRepository->with(['documents'])->find($id);
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            Log::error('Error updating transaction: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a transaction.
     */
    public function delete(int $id)
    {
        $currentUser = auth('api')->user();
        if (!$currentUser) {
            throw new BusinessException(ExceptionCode::UNAUTHENTICATED, 'Unauthenticated', 401);
        }

        $transaction = $this->transactionRepository->find($id);
        if (!$transaction) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Giao dịch không tồn tại', 404);
        }

        $this->beginTransaction();
        try {
            // Deleting the transaction will automatically disassociate or delete the t_documents because of migration constraints or we can do it explicitly
            Document::where('transaction_id', $id)->update([
                'transaction_id' => null,
                'documentable_id' => null,
                'documentable_type' => null,
            ]);

            $deleted = $transaction->delete();
            $this->commitTransaction();

            return $deleted;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            Log::error('Error deleting transaction: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Helper to auto-calculate tax and net amounts.
     */
    protected function calculateTaxAndAmounts(array $data, ?Transaction $existing = null): array
    {
        $amount = isset($data['amount']) ? (float) $data['amount'] : ($existing ? (float) $existing->amount : 0.0);
        $taxRateType = isset($data['tax_rate_type']) ? $data['tax_rate_type'] : ($existing ? $existing->tax_rate_type : TaxRateTypeConst::NONE);
        
        $rate = TaxRateTypeConst::getRate($taxRateType);
        
        // Auto calculate net_amount and tax_amount based on total amount and rate type
        if (!isset($data['net_amount']) || $data['net_amount'] === null) {
            $data['net_amount'] = round($amount / (1 + $rate), 2);
        } else {
            $data['net_amount'] = (float) $data['net_amount'];
        }

        if (!isset($data['tax_amount']) || $data['tax_amount'] === null) {
            $data['tax_amount'] = round($amount - $data['net_amount'], 2);
        } else {
            $data['tax_amount'] = (float) $data['tax_amount'];
        }

        return $data;
    }
}
