<?php

namespace App\Http\Controllers\Api\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\Finance\WithdrawalResource;
use App\Services\Admin\Finance\WithdrawalService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function __construct(
        protected WithdrawalService $withdrawalService
    ) {}

    public function index(Request $request)
    {
        $withdrawals = $this->withdrawalService->list($request);

        return Response::pagination(
            WithdrawalResource::collection($withdrawals),
            $withdrawals->total(),
            $withdrawals->currentPage(),
            $withdrawals->perPage()
        );
    }

    public function show($id)
    {
        $withdrawal = $this->withdrawalService->show($id);

        return Response::success((new WithdrawalResource($withdrawal))->resolve());
    }
}
