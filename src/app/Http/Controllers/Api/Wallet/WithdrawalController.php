<?php

namespace App\Http\Controllers\Api\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\Withdrawal\CreateWithdrawalRequest;
use App\Http\Requests\Wallet\Withdrawal\ListWithdrawalsRequest;
use App\Http\Resources\Wallet\WithdrawalResource;
use App\Services\Wallet\WithdrawalService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    protected $withdrawalService;

    public function __construct(WithdrawalService $withdrawalService)
    {
        $this->withdrawalService = $withdrawalService;
    }

    /**
     * Create withdrawal request
     * POST /api/worker/wallet/withdrawals
     */
    public function create(CreateWithdrawalRequest $request)
    {
        $user = $request->user();
        $withdrawal = $this->withdrawalService->create($request->validated(), $user->id);

        return Response::created((new WithdrawalResource($withdrawal))->resolve());
    }

    /**
     * List withdrawals
     * GET /api/worker/wallet/withdrawals
     */
    public function list(ListWithdrawalsRequest $request)
    {
        $user = $request->user();
        $withdrawals = $this->withdrawalService->list($request, $user->id);

        return Response::pagination(
            WithdrawalResource::collection($withdrawals),
            $withdrawals->total(),
            $withdrawals->currentPage(),
            $withdrawals->perPage()
        );
    }

    /**
     * Get withdrawal detail
     * GET /api/worker/wallet/withdrawals/{id}
     */
    public function get($id, Request $request)
    {
        $user = $request->user();
        $withdrawal = $this->withdrawalService->get($id, $user->id);

        return Response::success((new WithdrawalResource($withdrawal))->resolve());
    }
}
