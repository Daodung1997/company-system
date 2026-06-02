<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Customer\ListCustomerRequest;
use App\Http\Requests\Admin\Customer\ToggleStatusRequest;
use App\Http\Requests\Admin\Customer\UpdateCustomerRequest;
use App\Http\Resources\Admin\Customer\CustomerResource;
use App\Services\Admin\AdminCustomerService;
use App\Supports\Facades\Response\Response;

class CustomerController extends Controller
{
    protected $service;

    public function __construct(AdminCustomerService $service)
    {
        $this->service = $service;
    }

    public function index(ListCustomerRequest $request)
    {
        $customers = $this->service->list($request->validated());

        return Response::success(CustomerResource::collection($customers)->response()->getData(true));
    }

    public function show($id)
    {
        $customer = $this->service->show($id);

        return Response::success((new CustomerResource($customer))->resolve());
    }

    public function update(UpdateCustomerRequest $request, $id)
    {
        $customer = $this->service->update($id, $request->validated());

        return Response::success((new CustomerResource($customer))->resolve());
    }

    public function toggleStatus(ToggleStatusRequest $request, $id)
    {
        $customer = $this->service->toggleStatus($id, $request->status, $request->reason);

        return Response::success(['message' => 'Customer status updated', 'status' => $customer->status]);
    }
}
