<?php

namespace App\Services\User;

use App\Constants\Commons\CommonAuthConst;
use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\User\UserRoleConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Constants\Master\Models\UserVerification\UserVerificationTypeConst;
use App\Constants\Master\Models\WorkerProfile\WorkerProfileStatus;
use App\Exceptions\BusinessException;
use App\Mail\ForgotPasswordMail;
use App\Mail\VerifyEmail;
use App\Models\User;
use App\Repositories\CustomerProfile\CustomerProfileRepository;
use App\Repositories\Otp\OtpRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\UserVerification\UserVerificationRepository;
use App\Repositories\WorkerProfile\WorkerProfileRepository;
use App\Services\Common\BaseAuthService;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class UserAuthService extends BaseAuthService
{
    protected $repository;

    protected $verificationRepository;

    protected $otpRepository;

    protected $customerProfileRepository;

    protected $workerProfileRepository;

    public function __construct(
        UserRepository $userRepository,
        UserVerificationRepository $verificationRepository,
        OtpRepository $otpRepository,
        CustomerProfileRepository $customerProfileRepository,
        WorkerProfileRepository $workerProfileRepository
    ) {
        $this->repository = $userRepository;
        $this->verificationRepository = $verificationRepository;
        $this->otpRepository = $otpRepository;
        $this->customerProfileRepository = $customerProfileRepository;
        $this->workerProfileRepository = $workerProfileRepository;
    }

    protected function guardName(): string
    {
        return CommonAuthConst::GUARD_API;
    }

    public function guard()
    {
        return auth()->guard($this->guardName());
    }

    /**
     * Register a new user and send OTP verification email
     *
     * @return User
     */
    public function register(array $data)
    {
        $this->beginTransaction();
        try {
            $user = $this->repository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => UserStatusConst::PENDING_VERIFICATION,
                'role' => null,
            ]);

            // Create OTP for email verification (stored in m_user_verifications)
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $this->verificationRepository->create([
                'user_id' => $user->id,
                'token' => $otp,
                'type' => UserVerificationTypeConst::REGISTER,
                'expires_at' => now()->addMinutes(10),
            ]);

            // Send OTP email
            try {
                Mail::to($user->email)->send(new VerifyEmail($otp, $user->name));
            } catch (\Exception $e) {
                Log::error('Failed to send verification email: '.$e->getMessage());
            }

            $this->commitTransaction();

            return $user;
        } catch (Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Verify email using OTP
     */
    public function verifyEmail(string $email, string $otp): bool
    {
        $user = $this->repository->findWhere(['email' => $email])->first();
        if (! $user) {
            throw new BusinessException(ExceptionCode::USER_NOT_FOUND, 'User not found', 404);
        }

        if ($user->status !== UserStatusConst::PENDING_VERIFICATION) {
            throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Email already verified', 400);
        }

        $record = $this->verificationRepository->findWhere([
            'user_id' => $user->id,
            'token' => $otp,
            'type' => UserVerificationTypeConst::REGISTER,
        ])->first();

        if (! $record) {
            throw new BusinessException(ExceptionCode::INVALID_OTP, 'Invalid OTP', 400);
        }

        if ($record->expires_at < now()) {
            throw new BusinessException(ExceptionCode::OTP_EXPIRED, 'OTP expired', 400);
        }

        $this->beginTransaction();
        try {
            $user->update([
                'status' => UserStatusConst::ACTIVE,
                'email_verified_at' => now(),
            ]);

            // Delete verification OTP record
            $record->delete();

            $this->commitTransaction();

            return true;
        } catch (Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Resend verification OTP
     */
    public function resendVerificationOtp(string $email): void
    {
        $user = $this->repository->findWhere(['email' => $email])->first();
        if (! $user) {
            throw new BusinessException(ExceptionCode::USER_NOT_FOUND, 'User not found', 404);
        }

        if ($user->status !== UserStatusConst::PENDING_VERIFICATION) {
            throw new BusinessException(ExceptionCode::INVALID_STATUS, 'Email already verified', 400);
        }

        $this->beginTransaction();
        try {
            // Delete old verification OTP
            $this->verificationRepository->deleteWhere([
                'user_id' => $user->id,
                'type' => UserVerificationTypeConst::REGISTER,
            ]);

            // Create new OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $this->verificationRepository->create([
                'user_id' => $user->id,
                'token' => $otp,
                'type' => UserVerificationTypeConst::REGISTER,
                'expires_at' => now()->addMinutes(10),
            ]);

            // Send OTP email
            try {
                Mail::to($user->email)->send(new VerifyEmail($otp, $user->name));
            } catch (\Exception $e) {
                Log::error('Failed to send verification email: '.$e->getMessage());
            }

            $this->commitTransaction();
        } catch (Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Login user
     *
     * @return array
     */
    public function login(array $credentials)
    {
        if (! $token = $this->guard()->attempt($credentials)) {
            throw new BusinessException(ExceptionCode::INVALID_CREDENTIALS, 'Invalid credentials', 401);
        }

        $user = $this->guard()->user();

        if ($user->status === UserStatusConst::BLOCKED) {
            $this->guard()->logout();
            throw new BusinessException(ExceptionCode::ACCOUNT_BLOCKED, 'Account blocked', 403);
        }

        if ($user->status === UserStatusConst::PENDING_VERIFICATION) {
            $this->guard()->logout();
            throw new BusinessException(ExceptionCode::ACCOUNT_NOT_VERIFIED, 'Please verify your email first', 403);
        }

        if ($user->status !== UserStatusConst::ACTIVE) {
            $this->guard()->logout();
            throw new BusinessException(ExceptionCode::ACCOUNT_NOT_VERIFIED, 'Account not active or not verified', 403);
        }

        $response = $this->respondWithToken($token);
        $response['needs_role_selection'] = ($user->role === null) || (count($user->getRolesMetadata()) > 1);
        $response['needs_profile_setup'] = $this->needsProfileSetup($user);

        return $response;
    }

    /**
     * Choose Role
     *
     * @return User
     */
    public function chooseRole(User $user, string $role)
    {
        $roleMap = [
            'customer' => UserRoleConst::CUSTOMER,
            'worker' => UserRoleConst::WORKER,
        ];

        $normalizedRole = strtolower($role);
        if (! array_key_exists($normalizedRole, $roleMap)) {
            throw new BusinessException(ExceptionCode::INVALID_ROLE, 'Invalid role', 400);
        }

        $roleId = $roleMap[$normalizedRole];

        $this->beginTransaction();
        try {
            $user->role = $roleId;
            $user->save();

            // Auto-create profile based on role
            if ($roleId === UserRoleConst::WORKER) {
                $this->workerProfileRepository->firstOrCreate([
                    'user_id' => $user->id,
                ], [
                    'profile_status' => WorkerProfileStatus::INCOMPLETE,
                ]);
            } elseif ($roleId === UserRoleConst::CUSTOMER) {
                $this->customerProfileRepository->firstOrCreate([
                    'user_id' => $user->id,
                ], []);
            }

            $this->commitTransaction();

            // Refresh user to get latest relations
            $user = $user->fresh();

            // Determine redirection target
            $redirectTo = 'home';
            if ($roleId === UserRoleConst::CUSTOMER) {
                $profile = $user->customerProfile;
                if (! $profile || empty($profile->phone)) {
                    $redirectTo = 'profile';
                }
            } elseif ($roleId === UserRoleConst::WORKER) {
                $profile = $user->workerProfile;
                if (! $profile || $profile->profile_status === WorkerProfileStatus::INCOMPLETE) {
                    $redirectTo = 'register';
                } elseif ($profile->profile_status === WorkerProfileStatus::PENDING) {
                    $redirectTo = 'pending_screen';
                }
            }

            // Re-issue token with updated user (including new role)
            $token = $this->guard()->login($user);
            $response = $this->respondWithToken($token);
            $response['redirect_to'] = $redirectTo;
            $response['needs_role_selection'] = false;
            $response['needs_profile_setup'] = $this->needsProfileSetup($user);

            return $response;
        } catch (Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Check if user needs to set up their profile
     * Returns true when no linked CustomerProfile/WorkerProfile record exists
     */
    private function needsProfileSetup(User $user): bool
    {
        if ($user->role === null) {
            return true;
        }

        if ($user->role === UserRoleConst::CUSTOMER) {
            $profile = $user->customerProfile;
            if (! $profile) {
                return true;
            }

            return empty($profile->phone);
        }

        if ($user->role === UserRoleConst::WORKER) {
            $profile = $user->workerProfile;
            if (! $profile) {
                return true;
            }

            return $profile->profile_status === WorkerProfileStatus::INCOMPLETE;
        }

        return false;
    }

    /**
     * Forgot Password
     */
    public function forgotPassword(string $email)
    {
        $ip = request()->ip();
        $throttleKey = 'forgot-password:'.$email.'|'.$ip;
        $dailyThrottleKey = 'forgot-password-daily:'.$email.'|'.$ip;

        $perMinuteLimit = config('auth.otp_rate_limit.forgot_password_per_minute', 5);
        $perDayLimit = config('auth.otp_rate_limit.forgot_password_per_day', 30);

        if (RateLimiter::tooManyAttempts($throttleKey, $perMinuteLimit) || RateLimiter::tooManyAttempts($dailyThrottleKey, $perDayLimit)) {
            throw new BusinessException(ExceptionCode::RATE_LIMIT_EXCEEDED, 'Too many requests', 429);
        }

        RateLimiter::hit($throttleKey, 60); // 1 minute window
        RateLimiter::hit($dailyThrottleKey, 86400); // 24 hour window

        $user = $this->repository->findWhere(['email' => $email])->first();

        // Unified Response: Don't reveal if user exists
        if (! $user) {
            return;
        }

        $this->beginTransaction();
        try {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Delete old
            $this->otpRepository->deleteWhere([
                'tokenable_id' => $user->id,
                'tokenable_type' => get_class($user),
            ]);

            // Create new (hashed token)
            $this->otpRepository->create([
                'tokenable_id' => $user->id,
                'tokenable_type' => get_class($user),
                'token' => Hash::make($otp),
                'attempts' => 0,
                'created_at' => now(),
                'expires_at' => now()->addMinutes(5),
            ]);

            // Send Email (send plain otp)
            try {
                $locale = app()->getLocale();
                Mail::to($user->email)->send(new ForgotPasswordMail([
                    'token' => $otp,
                    'email' => $email,
                    'name' => $user->name,
                ], $locale));
            } catch (\Exception $e) {
                Log::error('Failed to send forgot password email: '.$e->getMessage());
            }

            $this->commitTransaction();
        } catch (Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Reset Password
     */
    public function resetPassword(array $data)
    {
        $ip = request()->ip();
        $throttleKey = 'reset-password:'.$data['email'].'|'.$ip;

        $resetPerMinuteLimit = config('auth.otp_rate_limit.reset_password_per_minute', 5);

        if (RateLimiter::tooManyAttempts($throttleKey, $resetPerMinuteLimit)) {
            throw new BusinessException(ExceptionCode::RATE_LIMIT_EXCEEDED, 'Too many requests', 429);
        }

        RateLimiter::hit($throttleKey, 60);

        $user = $this->repository->findWhere(['email' => $data['email']])->first();

        if (! $user) {
            throw new BusinessException(ExceptionCode::OTP_INVALID_OR_EXPIRED, 'Invalid OTP or email', 400);
        }

        if ($user->status !== UserStatusConst::ACTIVE) {
            throw new BusinessException(ExceptionCode::ACCOUNT_DISABLED, 'Account is disabled', 403);
        }

        $record = $this->otpRepository->findWhere([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
        ])->first();

        if (! $record) {
            throw new BusinessException(ExceptionCode::OTP_INVALID_OR_EXPIRED, 'Invalid OTP or expired', 400);
        }

        // Increment attempts immediately
        $record->increment('attempts');

        $maxWrongAttempts = config('auth.otp_rate_limit.max_wrong_attempts', 5);

        if ($record->attempts > $maxWrongAttempts) {
            $record->delete();
            throw new BusinessException(ExceptionCode::OTP_TOO_MANY_ATTEMPTS, 'Too many failed attempts. OTP invalidated.', 429);
        }

        if (! Hash::check($data['token'], $record->token)) {
            throw new BusinessException(ExceptionCode::OTP_INVALID_OR_EXPIRED, 'Invalid OTP', 400);
        }

        if ($record->expires_at < now()) { // Check expiration
            throw new BusinessException(ExceptionCode::OTP_INVALID_OR_EXPIRED, 'OTP expired', 400);
        }

        $this->beginTransaction();
        try {
            $user->password = Hash::make($data['password']);
            $user->save();

            // Delete OTP
            $record->delete();

            // Clear rate limit on success
            RateLimiter::clear($throttleKey);

            $this->commitTransaction();
        } catch (Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }
}
