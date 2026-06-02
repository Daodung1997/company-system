<?php

namespace App\Http\Controllers\Common;

use App\Constants\Commons\CommonConst;
use App\Constants\Commons\CommonTokenConst;
use App\Constants\Master\Resource\LocationResourceConst;
use App\Exceptions\InvalidAuthException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Common\Auth\ForgotPasswordRequest;
use App\Http\Requests\Common\Auth\ResetPasswordRequest;
use App\Http\Requests\Common\Auth\UpdatePasswordRequest;
use App\Http\Requests\Common\AuthCustomer\ForgotPasswordCustomerRequest;
use App\Http\Requests\Common\AuthCustomer\LoginCustomerRequest;
use App\Http\Requests\Common\AuthCustomer\ResetPasswordCustomerRequest;
use App\Http\Requests\Customer\RegisterRequest;
use App\Http\Resources\Master\Customer\CustomerResource;
use App\Services\Common\CustomerAuthService;
use App\Supports\Facades\Response\Response;
use App\Utils\Helper;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthCustomerController extends Controller
{
    public function __construct(protected CustomerAuthService $authService)
    {
        $this->middleware('auth:customer', [
            'except' => [
                'login',
                'register',
                'refresh',
                'forgotPassword',
                'resetPassword',
                'loginGoogle',
            ],
        ]);
    }

    /**
     * @throws InvalidAuthException|JWTException
     */
    public function login(LoginCustomerRequest $request): JsonResponse
    {
        $token = $this->authService->login($request);

        if (! $token || ! isset($token['access_token'])) {
            throw new InvalidAuthException;
        }

        if (! empty($request->header(CommonTokenConst::FCM_TOKEN))
            && ! empty($request->header(CommonConst::DEVICE))
            && ! empty(@auth()->guard('customer')->user()->code)) {
        }

        return $this->createNewToken($token);
    }

    /**
     * @throws JWTException
     */
    protected function createNewToken(array $token): JsonResponse
    {

        $data = [
            CommonTokenConst::ACCESS_TOKEN => $token[CommonTokenConst::ACCESS_TOKEN],
            CommonTokenConst::TOKEN_TYPE => CommonConst::BEARER,
            CommonTokenConst::EXPIRES_IN => $token[CommonTokenConst::EXPIRES_IN],
            CommonTokenConst::REFRESH_TOKEN => $token[CommonTokenConst::REFRESH_TOKEN],
        ];

        if (empty($token[CommonTokenConst::ACCESS_TOKEN])) {
            return Response::failure([
                CommonConst::MESSAGE => 'auth.inactive',
            ]);
        }

        return Response::success($data);
    }

    /**
     * Refresh a token.
     *
     * @throws JWTException
     */
    public function refresh(Request $request): JsonResponse
    {

        $token = $this->authService->refresh($request);

        if (! $token || ! isset($token['access_token'])) {
            throw new InvalidAuthException;
        }

        return $this->createNewToken($token);
    }

    /**
     * @throws JWTException
     */
    public function logout(): JsonResponse
    {
        $user = auth('customer')->user();
        $user->token()->revoke();

        return Response::success([CommonConst::MESSAGE => 'auth.logout']);
    }

    /**
     * @throws Exception
     */
    public function changePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = auth()->guard('customer')->user();
        $this->authService->updatePassword($user, $request);

        return Response::success();
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $customer = $this->authService->register($request);

            return Response::success([LocationResourceConst::USER => new CustomerResource($customer)]);
        } catch (Exception $e) {
            return Response::failure([CommonConst::MESSAGE => $e->getMessage()]);
        }
    }

    /**
     * @param  ForgotPasswordRequest  $request
     */
    public function forgotPassword(ForgotPasswordCustomerRequest $request): JsonResponse
    {
        try {
            $type = $request->input('type', 'customer');
            $this->authService->forgotPassword($request->email, $type);

            return Response::success([CommonConst::MESSAGE => 'password.forgot.success']);
        } catch (Exception $e) {
            return Response::failure([CommonConst::MESSAGE => $e->getMessage()]);
        }
    }

    /**
     * @param  ResetPasswordRequest  $request
     */
    public function resetPassword(ResetPasswordCustomerRequest $request): JsonResponse
    {
        try {
            $type = $request->input('type', 'customer');
            $this->authService->resetPassword(
                $request->token,
                $request->email,
                $request->password,
                Helper::getCurrentAuthGuard()
            );

            return Response::success([CommonConst::MESSAGE => 'password.reset.success']);
        } catch (Exception $e) {
            return Response::failure([CommonConst::MESSAGE => $e->getMessage()]);
        }
    }

    /**
     * Login with Google OAuth
     *
     * @throws InvalidAuthException|JWTException
     */
    public function loginGoogle(Request $request): JsonResponse
    {
        $token = $this->authService->loginGoogle($request);

        if (! $token || ! isset($token['access_token'])) {
            throw new InvalidAuthException;
        }

        return $this->createNewToken($token);
    }
}
