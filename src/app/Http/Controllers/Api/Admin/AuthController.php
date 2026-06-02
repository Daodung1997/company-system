<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Services\Admin\AdminAuthService;
use App\Supports\Facades\Response\Response;

class AuthController extends Controller
{
    protected $service;

    public function __construct(AdminAuthService $service)
    {
        $this->service = $service;
    }

    public function login(LoginRequest $request)
    {
        $result = $this->service->login($request->validated());

        return Response::success($result);
    }

    public function logout()
    {
        $this->service->logout();

        return Response::success(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        $result = $this->service->refresh();

        return Response::success($result);
    }
}
