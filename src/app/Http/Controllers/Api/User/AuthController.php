<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Auth\ChooseRoleRequest;
use App\Http\Requests\User\Auth\ForgotPasswordRequest;
use App\Http\Requests\User\Auth\LoginRequest;
use App\Http\Requests\User\Auth\RegisterRequest;
use App\Http\Requests\User\Auth\ResendVerificationOtpRequest;
use App\Http\Requests\User\Auth\ResetPasswordRequest;
use App\Http\Requests\User\Auth\VerifyEmailRequest;
use App\Services\User\UserAuthService;
use App\Supports\Facades\Response\Response;

class AuthController extends Controller
{
    protected $service;

    public function __construct(UserAuthService $service)
    {
        $this->service = $service;
    }

    public function register(RegisterRequest $request)
    {
        $user = $this->service->register($request->validated());

        return Response::created(['message' => 'Registration successful. Please check your email to verify account.']);
    }

    public function verifyEmail(VerifyEmailRequest $request)
    {
        $this->service->verifyEmail($request->email, $request->otp);

        return Response::success(['message' => 'Email verified successfully']);
    }

    public function resendVerificationOtp(ResendVerificationOtpRequest $request)
    {
        $this->service->resendVerificationOtp($request->email);

        return Response::success(['message' => 'Verification OTP sent']);
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

    public function chooseRole(ChooseRoleRequest $request)
    {
        $result = $this->service->chooseRole(auth()->user(), $request->role);

        return Response::success($result);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $this->service->forgotPassword($request->email);

        return Response::success(['message' => 'Nếu email tồn tại trong hệ thống, mã OTP đã được gửi.']);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $this->service->resetPassword($request->validated());

        return Response::success(['message' => 'Password reset successfully']);
    }
}
