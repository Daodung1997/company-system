<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Area\ListAreaRequest;
use App\Http\Resources\Area\AreaResource;
use App\Services\Area\AreaService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\JsonResponse;

class AreaController extends Controller
{
    public function __construct(protected AreaService $areaService) {}

    public function index(ListAreaRequest $request): JsonResponse
    {
        $filters = array_filter($request->validated(), fn ($v) => ! is_null($v));
        $areas = $this->areaService->list($filters);

        return Response::success(AreaResource::collection($areas)->resolve());
    }
}
