<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Resources\Transaction\TransactionResource;
use App\Services\Transaction\TransactionService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(protected TransactionService $transactionService) {}

    /**
     * GET /api/transactions - List transactions with filters
     */
    public function index(Request $request)
    {
        $filters = $request->only(['q', 'type', 'status', 'payment_method', 'category', 'start_date', 'end_date']);
        $transactions = $this->transactionService->list($filters);

        return Response::success(TransactionResource::collection($transactions)->resolve());
    }

    /**
     * GET /api/transactions/{id} - Show details of a transaction
     */
    public function show(int $id)
    {
        $transaction = $this->transactionService->show($id);

        return Response::success((new TransactionResource($transaction))->resolve());
    }

    /**
     * POST /api/transactions - Create a new transaction
     */
    public function store(StoreTransactionRequest $request)
    {
        $transaction = $this->transactionService->create($request->validated());

        return Response::success((new TransactionResource($transaction))->resolve());
    }

    /**
     * PUT /api/transactions/{id} - Update an existing transaction
     */
    public function update(int $id, StoreTransactionRequest $request)
    {
        $transaction = $this->transactionService->update($id, $request->validated());

        return Response::success((new TransactionResource($transaction))->resolve());
    }

    /**
     * DELETE /api/transactions/{id} - Delete a transaction
     */
    public function destroy(int $id)
    {
        $this->transactionService->delete($id);

        return Response::success(['message' => 'Giao dịch đã được xóa thành công.']);
    }
}
