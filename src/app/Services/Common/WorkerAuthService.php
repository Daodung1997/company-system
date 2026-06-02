<?php

namespace App\Services\Common;

use App\Constants\Commons\CommonAuthConst;
use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\User\UserRoleConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Exceptions\BusinessException;
use App\Exceptions\InvalidAuthException;
use App\Http\Requests\Common\AuthWorker\LoginWorkerRequest;
use App\Models\User;

class WorkerAuthService extends BaseAuthService
{
    protected function guardName(): string
    {
        return CommonAuthConst::GUARD_API;
    }

    public function guard()
    {
        return auth()->guard($this->guardName());
    }

    /**
     * Login worker
     *
     * @throws InvalidAuthException
     */
    public function login(LoginWorkerRequest $request): array
    {
        $credentials = $request->only('email', 'password');

        if (! $token = $this->guard()->attempt($credentials)) {
            throw new BusinessException(ExceptionCode::INVALID_CREDENTIALS, 'Invalid credentials', 401);
        }

        $user = $this->guard()->user();

        // Check if user is a worker
        if ($user->role !== UserRoleConst::WORKER) {
            $this->guard()->logout();
            throw new BusinessException(ExceptionCode::FORBIDDEN, 'Unauthorized access: Only workers are allowed.', 403);
        }

        if ($user->status === UserStatusConst::BLOCKED) {
            $this->guard()->logout();
            throw new BusinessException(ExceptionCode::ACCOUNT_BLOCKED, 'Account blocked', 403);
        }

        $response = $this->respondWithToken($token);

        $profile = $user->workerProfile;

        // Merge profile_status into the user object within the response
        $response['user']['profile_status'] = $profile ? $profile->profile_status : 'incomplete';

        return $response;
    }
}
