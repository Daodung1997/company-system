<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Worker\ListWorkerRequest;
use App\Http\Requests\Admin\Worker\RejectWorkerRequest;
use App\Http\Requests\Admin\Worker\SuspendWorkerRequest;
use App\Http\Requests\Admin\Worker\UpdateWorkerRequest;
use App\Http\Resources\Admin\Worker\WorkerRegistrationHistoryResource;
use App\Http\Resources\Admin\Worker\WorkerResource;
use App\Services\Admin\AdminWorkerService;
use App\Supports\Facades\Response\Response;

class WorkerController extends Controller
{
    protected $service;

    public function __construct(AdminWorkerService $service)
    {
        $this->service = $service;
    }

    public function index(ListWorkerRequest $request)
    {
        $data = $this->service->list($request->validated());

        return Response::success(
            WorkerResource::collection($data)->response()->getData(true)
        );
    }

    public function show($id)
    {
        $data = $this->service->show($id);

        return Response::success(
            (new WorkerResource($data))->resolve()
        );
    }

    public function update($id, UpdateWorkerRequest $request)
    {
        $data = $this->service->update($id, $request->validated());

        return Response::success(
            (new WorkerResource($data))->resolve()
        );
    }

    public function approve($id)
    {
        $data = $this->service->approve($id);

        return Response::success(
            (new WorkerResource($data))->resolve()
        );
    }

    public function reject($id, RejectWorkerRequest $request)
    {
        $data = $this->service->reject($id, $request->input('reason'));

        return Response::success(
            (new WorkerResource($data))->resolve()
        );
    }

    public function suspend($id, SuspendWorkerRequest $request)
    {
        $data = $this->service->suspend($id, $request->input('reason'));

        return Response::success(
            (new WorkerResource($data))->resolve()
        );
    }

    public function activate($id)
    {
        $data = $this->service->activate($id);

        return Response::success(
            (new WorkerResource($data))->resolve()
        );
    }

    public function registrationHistory($id)
    {
        $data = $this->service->getRegistrationHistory($id);

        return Response::success(
            WorkerRegistrationHistoryResource::collection($data)->resolve()
        );
    }
}
