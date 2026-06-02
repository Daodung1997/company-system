<?php

namespace App\Http\Controllers\Api\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Finance\IndexStatisticRequest;
use App\Http\Resources\Admin\Finance\CashFlowStatisticResource;
use App\Http\Resources\Admin\Finance\ServiceRevenueStatisticResource;
use App\Http\Resources\Admin\Finance\StatisticResource;
use App\Services\Admin\Finance\StatisticService;
use App\Supports\Facades\Response\Response;

class StatisticController extends Controller
{
    public function __construct(
        protected StatisticService $statisticService
    ) {}

    /**
     * Profit statistics
     */
    public function profit(IndexStatisticRequest $request)
    {
        $data = $this->statisticService->profit($request);

        return Response::success((new StatisticResource($data))->resolve());
    }

    /**
     * Cash flow statistics
     */
    public function cashFlow(IndexStatisticRequest $request)
    {
        $data = $this->statisticService->cashFlow($request);

        return Response::success((new CashFlowStatisticResource($data))->resolve());
    }

    /**
     * Service revenue statistics
     */
    public function serviceRevenue(IndexStatisticRequest $request)
    {
        $data = $this->statisticService->serviceRevenue($request);

        return Response::success((new ServiceRevenueStatisticResource($data))->resolve());
    }
}
