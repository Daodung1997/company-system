<?php

namespace App\Http\Controllers\Api\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\BankAccount\CreateBankAccountRequest;
use App\Http\Requests\Wallet\BankAccount\UpdateBankAccountRequest;
use App\Http\Resources\Wallet\BankAccountResource;
use App\Services\Wallet\BankAccountService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    protected $bankAccountService;

    public function __construct(BankAccountService $bankAccountService)
    {
        $this->bankAccountService = $bankAccountService;
    }

    /**
     * List bank accounts
     * GET /api/worker/bank-accounts
     */
    public function list(Request $request)
    {
        $user = $request->user();
        $bankAccounts = $this->bankAccountService->list($request, $user->id);

        return Response::success(BankAccountResource::collection($bankAccounts)->resolve());
    }

    /**
     * Create bank account
     * POST /api/worker/bank-accounts
     */
    public function create(CreateBankAccountRequest $request)
    {
        $user = $request->user();
        $bankAccount = $this->bankAccountService->create($request->validated(), $user->id);

        return Response::created((new BankAccountResource($bankAccount))->resolve());
    }

    /**
     * Update bank account
     * PUT /api/worker/bank-accounts/{id}
     */
    public function update($id, UpdateBankAccountRequest $request)
    {
        $user = $request->user();
        $bankAccount = $this->bankAccountService->update($id, $request->validated(), $user->id);

        return Response::success((new BankAccountResource($bankAccount))->resolve());
    }

    /**
     * Delete bank account
     * DELETE /api/worker/bank-accounts/{id}
     */
    public function delete($id, Request $request)
    {
        $user = $request->user();
        $this->bankAccountService->delete($id, $user->id);

        return Response::success([], 'Đã xoá tài khoản ngân hàng');
    }
}
