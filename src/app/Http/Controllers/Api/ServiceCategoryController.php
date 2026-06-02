<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceCategory\ServiceCategoryResource;
use App\Services\ServiceCategory\ServiceCategoryService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\JsonResponse;

class ServiceCategoryController extends Controller
{
    public function __construct(protected ServiceCategoryService $serviceCategoryService) {}

    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $categories = $this->serviceCategoryService->listActive($request->all());

        return Response::success(ServiceCategoryResource::collection($categories)->response()->getData(true));
    }
}
