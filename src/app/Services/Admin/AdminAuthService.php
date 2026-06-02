<?php

namespace App\Services\Admin;

use App\Constants\Commons\CommonAuthConst;
use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\Admin\AdminStatusConst;
use App\Exceptions\BusinessException;
use App\Repositories\Admin\AdminRepository;
use App\Services\Common\BaseAuthService;

class AdminAuthService extends BaseAuthService
{
    protected $repository;

    public function __construct(AdminRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function guardName(): string
    {
        return CommonAuthConst::GUARD_ADMIN;
    }

    public function guard()
    {
        return auth()->guard($this->guardName());
    }

    /**
     * Login admin
     *
     * @return array
     */
    public function login(array $credentials)
    {
        if (! $token = $this->guard()->attempt($credentials)) {
            throw new BusinessException(ExceptionCode::INVALID_CREDENTIALS, 'Invalid credentials', 401);
        }

        $user = auth($this->guardName())->user();
        if ($user->status != AdminStatusConst::ACTIVE) {
            throw new BusinessException(ExceptionCode::ACCOUNT_DISABLED, 'Account disabled', 403);
        }

        return $this->respondWithToken($token);
    }
}
