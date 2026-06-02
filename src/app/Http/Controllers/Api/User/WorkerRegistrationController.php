<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\WorkerRegistration\ResubmitRegistrationRequest;
use App\Http\Requests\User\WorkerRegistration\SubmitRegistrationRequest;
use App\Services\User\WorkerRegistrationService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\JsonResponse;

class WorkerRegistrationController extends Controller
{
    protected WorkerRegistrationService $registrationService;

    public function __construct(WorkerRegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    /**
     * Submit worker registration
     * POST /api/worker/registration
     */
    public function submit(SubmitRegistrationRequest $request): JsonResponse
    {
        $profile = $this->registrationService->submitRegistration($request->all());

        return Response::created([
            'id' => $profile->id,
            'profile_status' => $profile->profile_status,
            'message' => 'Hồ sơ đã được gửi. Vui lòng chờ Admin duyệt.',
        ]);
    }

    /**
     * Get registration status
     * GET /api/worker/registration/status
     */
    public function getStatus(): JsonResponse
    {
        $status = $this->registrationService->getRegistrationStatus();

        return Response::success($status);
    }

    /**
     * Resubmit registration after rejection
     * PUT /api/worker/registration
     */
    public function resubmit(ResubmitRegistrationRequest $request): JsonResponse
    {
        $profile = $this->registrationService->resubmitRegistration($request->all());

        return Response::success([
            'id' => $profile->id,
            'profile_status' => $profile->profile_status,
            'message' => 'Hồ sơ đã được gửi lại. Vui lòng chờ Admin duyệt.',
        ]);
    }
}
