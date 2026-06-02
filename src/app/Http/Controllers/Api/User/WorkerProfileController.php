<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\WorkerProfile\ToggleAvailabilityRequest;
use App\Http\Requests\User\WorkerProfile\UpdateWorkerAreasRequest;
use App\Http\Requests\User\WorkerProfile\UpdateWorkerProfileRequest;
use App\Http\Requests\User\WorkerProfile\UpdateWorkerServicesRequest;
use App\Http\Resources\User\WorkerProfile\WorkerProfileResource;
use App\Services\User\WorkerProfileService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class WorkerProfileController extends Controller
{
    protected $service;

    public function __construct(WorkerProfileService $service)
    {
        $this->service = $service;
    }

    public function show(Request $request)
    {
        $userId = $request->user()->id;
        $profile = $this->service->getProfile($userId);

        if (! $profile) {
            return Response::notFound('Worker profile not found');
        }

        return Response::success((new WorkerProfileResource($profile))->resolve());
    }

    public function update(UpdateWorkerProfileRequest $request)
    {
        $userId = $request->user()->id;
        $data = $request->validated();

        $profile = $this->service->updateProfile($userId, $data);
        // Refresh with relations for resource usually, or just return basic
        // For consistency, let's fetch full profile or at least load what's needed.
        // But getProfile already does loading.
        $profile = $this->service->getProfile($userId);

        return Response::success((new WorkerProfileResource($profile))->resolve());
    }

    public function toggleAvailability(ToggleAvailabilityRequest $request)
    {
        $userId = $request->user()->id;
        $this->service->toggleAvailability($userId, $request->validated());

        $profile = $this->service->getProfile($userId);

        return Response::success((new WorkerProfileResource($profile))->resolve());
    }

    public function updateAreas(UpdateWorkerAreasRequest $request)
    {
        $userId = $request->user()->id;
        $areaIds = $request->validated()['area_ids'];

        $this->service->updateAreas($userId, $areaIds);

        $profile = $this->service->getProfile($userId);

        return Response::success((new WorkerProfileResource($profile))->resolve());
    }

    public function updateServices(UpdateWorkerServicesRequest $request)
    {
        $userId = $request->user()->id;
        $serviceIds = $request->validated()['service_category_ids'];

        $this->service->updateServices($userId, $serviceIds);

        $profile = $this->service->getProfile($userId);

        return Response::success((new WorkerProfileResource($profile))->resolve());
    }
}
