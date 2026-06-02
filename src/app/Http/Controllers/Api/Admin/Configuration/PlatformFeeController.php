<?php

namespace App\Http\Controllers\Api\Admin\Configuration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Configuration\PlatformFee\CreatePlatformFeeRequest;
use App\Http\Requests\Admin\Configuration\PlatformFee\UpdatePlatformFeeRequest;
use App\Http\Resources\Admin\Configuration\PlatformFeeResource;
use App\Services\Admin\Configuration\PlatformFeeService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class PlatformFeeController extends Controller
{
    protected $service;

    public function __construct(PlatformFeeService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $result = $this->service->list($request->all());

        return Response::success(PlatformFeeResource::collection($result)->response()->getData(true));
    }

    public function store(CreatePlatformFeeRequest $request)
    {
        $result = $this->service->create($request->validated());

        return Response::created((new PlatformFeeResource($result))->resolve());
    }

    public function update(UpdatePlatformFeeRequest $request, $id)
    {
        $result = $this->service->update($id, $request->validated());

        return Response::success((new PlatformFeeResource($result))->resolve());
    }
}
