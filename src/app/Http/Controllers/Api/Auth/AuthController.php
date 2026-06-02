<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ChangePasswordRequest;
use App\Http\Requests\Api\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\Auth\EmployeeResource;
use App\Services\Api\Auth\AuthService;
use App\Supports\Facades\Response\Response;

class AuthController extends Controller
{
    protected $service;

    public function __construct(AuthService $service)
    {
        $this->service = $service;
    }

    /**
     * Employee Login
     */
    public function login(LoginRequest $request)
    {
        $result = $this->service->login($request->validated());

        return Response::success($result);
    }

    /**
     * Get Current Employee Profile
     */
    public function me()
    {
        $employee = auth('api')->user();
        $employee->load(['department', 'jobTitle']);

        return Response::success((new EmployeeResource($employee))->resolve());
    }

    /**
     * Employee Logout
     */
    public function logout()
    {
        $this->service->logout();

        return Response::success(['message' => 'Đăng xuất thành công.']);
    }

    /**
     * Refresh JWT Token
     */
    public function refresh()
    {
        $result = $this->service->refresh();

        return Response::success($result);
    }

    /**
     * Forgot Password - Send OTP reset token to email
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $result = $this->service->forgotPassword($request->validated());

        return Response::success($result);
    }

    /**
     * Reset Password - Verify OTP token and set new password
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $result = $this->service->resetPassword($request->validated());

        return Response::success($result);
    }

    /**
     * Change Password - Authenticated employee changes their own password
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $result = $this->service->changePassword($request->validated());

        return Response::success($result);
    }
}
