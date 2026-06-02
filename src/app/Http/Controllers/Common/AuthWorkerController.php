<?php

namespace App\Http\Controllers\Common;

use App\Constants\Commons\CommonConst;
use App\Http\Controllers\Controller;
use App\Http\Requests\Common\Auth\RegisterWorkerRequest;
use App\Http\Requests\Common\AuthWorker\LoginWorkerRequest;
use App\Services\Common\WorkerAuthService;
use App\Services\Common\WorkerRegistrationService;
use App\Supports\Facades\Response\Response;
use Exception;
use Illuminate\Http\JsonResponse;

class AuthWorkerController extends Controller
{
    public function __construct(
        protected WorkerRegistrationService $workerRegistrationService,
        protected WorkerAuthService $workerAuthService
    ) {}

    /**
     * Login worker
     */
    public function login(LoginWorkerRequest $request): JsonResponse
    {
        try {
            $data = $this->workerAuthService->login($request);

            return Response::success($data);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 401; // default to 401 if unhandled auth error
            if ($code < 100 || $code >= 600) {
                $code = 400;
            } // prevent invalid status code exception

            return Response::failure([CommonConst::MESSAGE => $e->getMessage()], $code);
        }
    }

    /**
     * Register a new worker with KYC
     */
    public function register(RegisterWorkerRequest $request): JsonResponse
    {
        try {
            $user = $this->workerRegistrationService->register(
                $request->validated(),
                [
                    'id_card_front' => $request->file('id_card_front'),
                    'id_card_back' => $request->file('id_card_back'),
                    'selfie' => $request->file('selfie'),
                ]
            );

            return Response::created([
                CommonConst::MESSAGE => 'worker.register.success',
                'user_id' => $user->id,
            ]);
        } catch (Exception $e) {
            return Response::failure([CommonConst::MESSAGE => $e->getMessage()], 400);
        }
    }
}
