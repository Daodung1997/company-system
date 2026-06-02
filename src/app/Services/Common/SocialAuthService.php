<?php

namespace App\Services\Common;

use App\Constants\Commons\CommonConst;
use App\Models\User;
use App\Services\AbstractService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthService extends AbstractService
{
    private const CUSTOMER_ROLE = 'customer';

    private const ACTIVE_STATUS = 'active';

    /**
     * Handle social login/register
     *
     * @throws Exception
     */
    public function handleSocialLogin(string $provider, string $token): array
    {
        $this->beginTransaction();
        try {
            // Validate token with provider
            $socialUser = $this->validateSocialToken($provider, $token);

            if (! $socialUser) {
                throw new Exception('social.invalid_token');
            }

            // Find or create user
            $user = $this->findOrCreateUser($socialUser, $provider);

            // Generate JWT token
            $jwtToken = Auth::guard(CommonConst::API)->login($user);

            $this->commitTransaction();

            return [
                'token' => $jwtToken,
                'user' => $user,
            ];
        } catch (Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Validate token with social provider
     */
    private function validateSocialToken(string $provider, string $token): ?object
    {
        try {
            return Socialite::driver($provider)->stateless()->userFromToken($token);
        } catch (Exception $e) {
            report($e);

            return null;
        }
    }

    /**
     * Find existing user or create new one
     */
    private function findOrCreateUser(object $socialUser, string $provider): User
    {
        // First try to find by social account link
        $user = User::whereHas('socialAccounts', function ($query) use ($provider, $socialUser) {
            $query->where('provider', $provider)
                ->where('provider_id', $socialUser->getId());
        })->first();

        if ($user) {
            return $user;
        }

        // Try to find by email
        if ($socialUser->getEmail()) {
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // Link social account to existing user
                $this->linkSocialAccount($user, $socialUser, $provider);

                return $user;
            }
        }

        // Create new user
        $user = User::create([
            'name' => $socialUser->getName() ?? 'User',
            'email' => $socialUser->getEmail(),
            'role' => self::CUSTOMER_ROLE,
            'status' => self::ACTIVE_STATUS,
            'email_verified_at' => now(),
        ]);

        // Link social account
        $this->linkSocialAccount($user, $socialUser, $provider);

        return $user;
    }

    /**
     * Link social account to user
     */
    private function linkSocialAccount(User $user, object $socialUser, string $provider): void
    {
        $user->socialAccounts()->updateOrCreate(
            [
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
            ],
            [
                'avatar_url' => $socialUser->getAvatar(),
            ]
        );
    }
}
