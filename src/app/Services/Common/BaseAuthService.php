<?php

namespace App\Services\Common;

use App\Constants\Commons\CommonAuthConst;
use App\Http\Resources\Admin\AdminResource;
use App\Http\Resources\User\UserResource;
use App\Services\AbstractService;
use Tymon\JWTAuth\Facades\JWTAuth;

abstract class BaseAuthService extends AbstractService
{
    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    abstract public function guard();

    /**
     * Get the password reset broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    // abstract public function broker(); // Might not need standard broker if we implement custom OTP

    protected function respondWithToken($token)
    {
        $user = auth($this->guardName())->user();
        $userResource = $this->guardName() === CommonAuthConst::GUARD_ADMIN
            ? new AdminResource($user)
            : new UserResource($user);

        return [
            CommonAuthConst::ACCESS_TOKEN => $token,
            CommonAuthConst::TOKEN_TYPE => 'bearer',
            CommonAuthConst::REFRESH_TOKEN => $this->createRefreshToken(),
            CommonAuthConst::EXPIRES_IN => auth($this->guardName())->factory()->getTTL() * 60,
            'user' => $userResource,
        ];
    }

    abstract protected function guardName(): string;

    /**
     * Invalidate the token.
     *
     * @return void
     */
    public function logout()
    {
        auth($this->guardName())->logout();
    }

    /**
     * Refresh a token.
     *
     * @return array
     */
    public function refresh()
    {
        // Because the route is now public (to bypass the 401 Unauthorized middleware rejection for expired tokens),
        // we must manually extract and refresh the token via the JWTAuth facade.
        try {
            $oldToken = JWTAuth::getToken();
            if (! $oldToken) {
                throw new \Tymon\JWTAuth\Exceptions\JWTException('Token not provided');
            }

            // Refresh the token explicitly using JWT store
            $newToken = JWTAuth::refresh($oldToken);

            // Set the new token in the guard so `user()` resolves properly
            JWTAuth::setToken($newToken);
            auth($this->guardName())->setToken($newToken);

            return $this->respondWithToken($newToken);
        } catch (\Tymon\JWTAuth\Exceptions\TokenBlacklistedException $e) {
            throw new \App\Exceptions\InvalidAuthException(__('auth.blacklist_token'));
        } catch (\Exception $e) {
            throw new \App\Exceptions\InvalidAuthException(__('auth.unauthorized'));
        }
    }

    protected function createRefreshToken()
    {
        // Custom logic to create long-lived refresh token if needed,
        // or just rely on JWT refresh.
        // For now, let's assume we use standard JWT refresh flow or a separate mechanism.
        // If using separate table for refresh tokens (Oauth style), implement here.
        // The project has m_user_tokens and m_admin_tokens.
        return null; // Placeholder implementation
    }
}
