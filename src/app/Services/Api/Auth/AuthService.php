<?php

namespace App\Services\Api\Auth;

use App\Constants\Commons\CommonAuthConst;
use App\Constants\Commons\ExceptionCode;
use App\Exceptions\BusinessException;
use App\Http\Resources\Api\Auth\EmployeeResource;
use App\Models\Employee;
use App\Models\PasswordReset;
use App\Repositories\Employee\EmployeeRepository;
use App\Services\Common\BaseAuthService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService extends BaseAuthService
{
    public function __construct(EmployeeRepository $employeeRepository)
    {
        $this->repository = $employeeRepository;
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
     * Authenticate employee and return JWT token response
     */
    public function login(array $data)
    {
        $username = $data['username'];
        $password = $data['password'];

        // Find employee by email or phone
        $employee = $this->repository->findByEmailOrPhone($username);

        if (!$employee || !Hash::check($password, $employee->password)) {
            throw new BusinessException(
                ExceptionCode::INVALID_CREDENTIALS,
                'Tài khoản hoặc mật khẩu không chính xác.',
                401
            );
        }

        if ($employee->status === 'INACTIVE') {
            throw new BusinessException(
                ExceptionCode::ACCOUNT_DISABLED,
                'Tài khoản đã bị vô hiệu hóa.',
                403
            );
        }

        // Generate JWT token from employee instance
        $token = $this->guard()->login($employee);

        return $this->respondWithToken($token);
    }

    /**
     * Override respondWithToken to return EmployeeResource
     */
    protected function respondWithToken($token)
    {
        $employee = $this->guard()->user();

        // Load department and jobTitle relations for login response metadata
        $employee->load(['department', 'jobTitle']);

        return [
            CommonAuthConst::ACCESS_TOKEN => $token,
            CommonAuthConst::TOKEN_TYPE => 'bearer',
            CommonAuthConst::EXPIRES_IN => $this->guard()->factory()->getTTL() * 60,
            'must_change_password' => (bool)$employee->must_change_password,
            'employee' => new EmployeeResource($employee),
        ];
    }

    /**
     * Forgot Password - Generate OTP reset token and send to employee email.
     * In production, this would dispatch a Mail notification.
     * For dev environment, the token is logged and returned in the response.
     */
    public function forgotPassword(array $data): array
    {
        $email = $data['email'];

        $employee = $this->repository->findByEmailOrPhone($email);

        if (!$employee) {
            throw new BusinessException(
                ExceptionCode::USER_NOT_FOUND,
                'Không tìm thấy tài khoản với email này.',
                404
            );
        }

        if ($employee->status === 'INACTIVE') {
            throw new BusinessException(
                ExceptionCode::ACCOUNT_DISABLED,
                'Tài khoản đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.',
                403
            );
        }

        // Rate limiting: check requests within the last minute
        $recentCount = PasswordReset::where('tokenable_type', Employee::class)
            ->where('tokenable_id', $employee->id)
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        $limitPerMinute = (int) config('app.otp_forgot_password_per_minute', 5);
        if ($recentCount >= $limitPerMinute) {
            throw new BusinessException(
                ExceptionCode::RATE_LIMIT_EXCEEDED,
                'Bạn đã yêu cầu quá nhiều lần. Vui lòng thử lại sau 1 phút.',
                429
            );
        }

        // Invalidate all previous tokens for this employee
        PasswordReset::where('tokenable_type', Employee::class)
            ->where('tokenable_id', $employee->id)
            ->delete();

        // Generate 6-digit OTP token
        $otpToken = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store the hashed token in DB
        PasswordReset::create([
            'tokenable_type' => Employee::class,
            'tokenable_id'   => $employee->id,
            'token'          => Hash::make($otpToken),
            'attempts'       => 0,
            'expires_at'     => now()->addMinutes(10),
            'created_at'     => now(),
        ]);

        // TODO: In production, dispatch Mail notification to $employee->email with $otpToken
        // For now in dev mode, we log it and return it in the response
        \Log::info("Password Reset OTP for {$employee->email}: {$otpToken}");

        $responseData = [
            'message' => 'Mã xác thực đã được gửi đến email của bạn.',
        ];

        // Only include OTP in dev/local environment for testing convenience
        if (app()->environment('local', 'testing')) {
            $responseData['debug_token'] = $otpToken;
        }

        return $responseData;
    }

    /**
     * Reset Password - Verify OTP token and set new password.
     */
    public function resetPassword(array $data): array
    {
        $email       = $data['email'];
        $otpToken    = $data['token'];
        $newPassword = $data['password'];

        $employee = $this->repository->findByEmailOrPhone($email);

        if (!$employee) {
            throw new BusinessException(
                ExceptionCode::USER_NOT_FOUND,
                'Không tìm thấy tài khoản với email này.',
                404
            );
        }

        // Find the latest valid reset token for this employee
        $resetRecord = PasswordReset::where('tokenable_type', Employee::class)
            ->where('tokenable_id', $employee->id)
            ->latest('created_at')
            ->first();

        if (!$resetRecord) {
            throw new BusinessException(
                ExceptionCode::INVALID_TOKEN,
                'Không tìm thấy yêu cầu đặt lại mật khẩu. Vui lòng gửi lại yêu cầu quên mật khẩu.',
                400
            );
        }

        // Check if token has expired
        if ($resetRecord->hasExpired()) {
            $resetRecord->delete();
            throw new BusinessException(
                ExceptionCode::TOKEN_EXPIRED,
                'Mã xác thực đã hết hạn. Vui lòng gửi lại yêu cầu quên mật khẩu.',
                400
            );
        }

        // Check max wrong attempts
        $maxAttempts = (int) config('app.otp_max_wrong_attempts', 5);
        if ($resetRecord->attempts >= $maxAttempts) {
            $resetRecord->delete();
            throw new BusinessException(
                ExceptionCode::OTP_TOO_MANY_ATTEMPTS,
                'Bạn đã nhập sai mã xác thực quá nhiều lần. Vui lòng gửi lại yêu cầu quên mật khẩu.',
                400
            );
        }

        // Verify OTP token
        if (!Hash::check($otpToken, $resetRecord->token)) {
            $resetRecord->increment('attempts');
            throw new BusinessException(
                ExceptionCode::INVALID_OTP,
                'Mã xác thực không chính xác.',
                400
            );
        }

        // All checks passed - update password
        $this->beginTransaction();
        try {
            $employee->password = $newPassword; // Will be auto-hashed via 'hashed' cast
            $employee->save();

            // Delete the used reset token
            $resetRecord->delete();

            $this->commitTransaction();
        } catch (\Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }

        return [
            'message' => 'Mật khẩu đã được đặt lại thành công. Vui lòng đăng nhập lại.',
        ];
    }

    /**
     * Change Password - Authenticated employee changes their own password.
     */
    public function changePassword(array $data): array
    {
        $employee       = $this->guard()->user();
        $currentPassword = $data['current_password'];
        $newPassword     = $data['password'];

        // Verify current password
        if (!Hash::check($currentPassword, $employee->password)) {
            throw new BusinessException(
                ExceptionCode::CURRENT_PASSWORD_NOT_MATCH,
                'Mật khẩu hiện tại không chính xác.',
                400
            );
        }

        // Update password
        $this->beginTransaction();
        try {
            $employee->password = $newPassword; // Auto-hashed via 'hashed' cast
            $employee->must_change_password = false;
            $employee->save();

            // Invalidate current JWT token and generate a new one
            $this->guard()->invalidate(true);
            $newToken = $this->guard()->login($employee);

            $this->commitTransaction();
        } catch (\Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }

        return [
            'message'      => 'Mật khẩu đã được thay đổi thành công.',
            'access_token' => $newToken,
            'token_type'   => 'bearer',
            'expires_in'   => $this->guard()->factory()->getTTL() * 60,
        ];
    }
}
