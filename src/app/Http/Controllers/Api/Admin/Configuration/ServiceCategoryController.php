<?php

namespace App\Http\Controllers\Api\Admin\Configuration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Configuration\ServiceCategory\ReorderServiceCategoriesRequest;
use App\Http\Requests\Admin\Configuration\ServiceCategory\StoreServiceCategoryRequest;
use App\Http\Requests\Admin\Configuration\ServiceCategory\UpdateServiceCategoryRequest;
use App\Http\Resources\Admin\Configuration\ServiceCategoryResource;
use App\Services\Admin\Configuration\ServiceCategoryService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class ServiceCategoryController extends Controller
{
    protected $service;

    public function __construct(ServiceCategoryService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $result = $this->service->list($request->all());

        return Response::success(ServiceCategoryResource::collection($result)->response()->getData(true));
    }

    public function store(StoreServiceCategoryRequest $request)
    {
        $result = $this->service->create($request->validated());

        return Response::created((new ServiceCategoryResource($result))->resolve());
    }

    public function show($id)
    {
        $result = $this->service->show($id);

        return Response::success((new ServiceCategoryResource($result))->resolve());
    }

    public function update(UpdateServiceCategoryRequest $request, $id)
    {
        $result = $this->service->update($id, $request->validated());

        return Response::success((new ServiceCategoryResource($result))->resolve());
    }

    public function destroy($id)
    {
        $this->service->delete($id);

        return Response::success(['message' => 'Category deleted successfully']);
    }

    public function reorder(ReorderServiceCategoriesRequest $request)
    {
        $result = $this->service->reorder($request->validated());

        return Response::success(ServiceCategoryResource::collection($result)->resolve());
    }
}
