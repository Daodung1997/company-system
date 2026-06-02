<?php

namespace App\Services\Common;

use App\Constants\Commons\CommonConst;
use App\Http\Requests\Common\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthService extends BaseAuthService
{
    public function __construct(User $user)
    {
        $this->model = $user;
        $this->guard = CommonConst::API;
    }

    public function login(LoginRequest $request): ?string
    {
        return Auth::guard($this->guard)->attempt($request->validated());
    }
}
