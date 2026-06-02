<?php

namespace App\Http\Controllers\Api\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\Transaction\ListTransactionsRequest;
use App\Http\Resources\Wallet\WalletBalanceResource;
use App\Http\Resources\Wallet\WalletTransactionResource;
use App\Services\Wallet\WalletService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Get wallet balance
     * GET /api/worker/wallet
     */
    public function getBalance(Request $request)
    {
        $user = $request->user();

        $balance = $this->walletService->getBalance($user);

        return Response::success((new WalletBalanceResource($balance))->resolve());
    }

    /**
     * List transactions
     * GET /api/worker/wallet/transactions
     */
    public function listTransactions(ListTransactionsRequest $request)
    {
        $user = $request->user();
        $transactions = $this->walletService->listTransactions($request, $user);

        return Response::pagination(
            WalletTransactionResource::collection($transactions),
            $transactions->total(),
            $transactions->currentPage(),
            $transactions->perPage()
        );
    }
}
