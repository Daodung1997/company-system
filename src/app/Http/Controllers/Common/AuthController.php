<?php

namespace App\Http\Controllers\Common;

use App\Constants\Commons\CommonConst;
use App\Constants\Commons\CommonTokenConst;
use App\Constants\Master\Resource\LocationResourceConst;
use App\Exceptions\InvalidAuthException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Common\Auth\ForgotPasswordRequest;
use App\Http\Requests\Common\Auth\LoginRequest;
use App\Http\Requests\Common\Auth\ResendOtpRequest;
use App\Http\Requests\Common\Auth\ResetPasswordRequest;
use App\Http\Requests\Common\Auth\SocialLoginRequest;
use App\Http\Requests\Common\Auth\UpdatePasswordRequest;
use App\Http\Requests\Common\Auth\VerifyOtpRequest;
use App\Http\Resources\Master\User\UserResource;
use App\Services\Common\AuthService;
use App\Services\Common\OtpService;
use App\Services\Common\SocialAuthService;
use App\Supports\Facades\Response\Response;
use Exception;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected OtpService $otpService,
        protected SocialAuthService $socialAuthService
    ) {
        $this->middleware('auth:api', [
            'except' => [
                'login',
                'refresh',
                'logout',
                'changePassword',
                'forgotPassword',
                'resetPassword',
                'verifyOtp',
                'resendOtp',
                'socialLogin',
            ],
        ]);
    }

    /**
     * @throws InvalidAuthException|JWTException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $token = $this->authService->login($request);

        if (! $token) {
            throw new InvalidAuthException;
        }

        if (! empty($request->header(CommonTokenConst::FCM_TOKEN))
            && ! empty($request->header(CommonConst::DEVICE))
            && ! empty(@auth()->user()->code)) {
            //            $this->fcmNotificationService->registerFcm($request, auth()->user()->code);
        }

        return $this->createNewToken($token);
    }

    /**
     * Refresh a token.
     *
     * @throws JWTException
     */
    public function refresh(): JsonResponse
    {
        return $this->createNewToken(auth()->refresh(), true);
    }

    /**
     * @throws JWTException
     */
    protected function createNewToken(string $token, $isRefresh = false): JsonResponse
    {
        $data = [
            CommonTokenConst::ACCESS_TOKEN => $token,
            CommonTokenConst::TOKEN_TYPE => CommonConst::BEARER,
            CommonTokenConst::EXPIRES_IN => auth()->factory()->getTTL() * 60,
        ];
        if (auth()->user()->isActive()) {
            if ($isRefresh) {
                return Response::success($data);
            }
            $data[LocationResourceConst::USER] = new UserResource(auth()->user());

            return Response::success($data);
        }

        return Response::failure([
            CommonConst::MESSAGE => 'auth.inactive',
        ]);
    }

    /**
     * @throws JWTException
     */
    public function logout(): JsonResponse
    {
        $token = JWTAuth::parseToken();
        $token->invalidate(true);

        return Response::success([CommonConst::MESSAGE => 'auth.logout']);
    }

    /**
     * @throws Exception
     */
    public function changePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $firstLogin = (int) $request->first_login;
        $user = auth()->user();

        if ($firstLogin) {
            $this->authService->updatePasswordForFirstLogin($user, $request);
        } else {
            $this->authService->updatePassword($user, $request);
        }

        return Response::success();
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->forgotPassword($request->email, 'user');

            return Response::success([CommonConst::MESSAGE => 'password.forgot.success']);
        } catch (Exception $e) {
            return Response::failure([CommonConst::MESSAGE => $e->getMessage()]);
        }
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {

        try {
            $this->authService->resetPassword(
                $request->token,
                $request->email,
                $request->password
            );

            return Response::success([CommonConst::MESSAGE => 'password.reset.success']);
        } catch (Exception $e) {
            return Response::failure([CommonConst::MESSAGE => $e->getMessage()]);
        }
    }

    /**
     * Verify OTP code
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $this->otpService->verifyOtp(
                $request->identifier,
                $request->code,
                $request->type
            );

            return Response::success([CommonConst::MESSAGE => 'otp.verified']);
        } catch (Exception $e) {
            return Response::failure([CommonConst::MESSAGE => $e->getMessage()], 400);
        }
    }

    /**
     * Resend OTP code
     */
    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        try {
            $this->otpService->resendOtp(
                $request->identifier,
                $request->type
            );

            return Response::success([CommonConst::MESSAGE => 'otp.sent']);
        } catch (Exception $e) {
            return Response::failure([CommonConst::MESSAGE => $e->getMessage()], 400);
        }
    }

    /**
     * Social Login (Google/Apple)
     */
    public function socialLogin(SocialLoginRequest $request): JsonResponse
    {
        try {
            $result = $this->socialAuthService->handleSocialLogin(
                $request->provider,
                $request->token
            );

            return Response::success([
                CommonTokenConst::ACCESS_TOKEN => $result['token'],
                CommonTokenConst::TOKEN_TYPE => CommonConst::BEARER,
                CommonTokenConst::EXPIRES_IN => auth()->factory()->getTTL() * 60,
                LocationResourceConst::USER => new UserResource($result['user']),
            ]);
        } catch (Exception $e) {
            return Response::failure([CommonConst::MESSAGE => $e->getMessage()], 400);
        }
    }
}
