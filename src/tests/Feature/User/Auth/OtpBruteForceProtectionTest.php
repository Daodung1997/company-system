<?php

namespace Tests\Feature\User\Auth;

use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class OtpBruteForceProtectionTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'status' => UserStatusConst::ACTIVE,
            'email_verified_at' => now(),
            'password' => Hash::make('Test@123'),
        ], $overrides));
    }

    private function createOtpRecord(User $user, string $plainOtp = '123456', array $overrides = []): PasswordReset
    {
        return PasswordReset::create(array_merge([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'token' => Hash::make($plainOtp),
            'attempts' => 0,
            'created_at' => now(),
            'expires_at' => now()->addMinutes(5),
        ], $overrides));
    }

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('forgot-password:test@example.com|127.0.0.1');
        RateLimiter::clear('forgot-password-daily:test@example.com|127.0.0.1');
        RateLimiter::clear('reset-password:test@example.com|127.0.0.1');
    }

    // ─── Forgot Password: Unified Response ──────────────────

    public function test_forgot_password_returns_200_for_existing_email()
    {
        Mail::fake();
        $user = $this->createActiveUser(['email' => 'test@example.com']);

        $response = $this->postJson('/api/user/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Nếu email tồn tại trong hệ thống, mã OTP đã được gửi.');
    }

    public function test_forgot_password_returns_200_for_non_existing_email()
    {
        Mail::fake();

        $response = $this->postJson('/api/user/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Nếu email tồn tại trong hệ thống, mã OTP đã được gửi.');
    }

    public function test_forgot_password_validation_error_without_email()
    {
        $response = $this->postJson('/api/user/auth/forgot-password', []);

        $response->assertStatus(422);
    }

    public function test_forgot_password_validation_error_invalid_email()
    {
        $response = $this->postJson('/api/user/auth/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
    }

    // ─── Forgot Password: OTP Hashing ───────────────────────

    public function test_forgot_password_stores_hashed_otp()
    {
        Mail::fake();
        $user = $this->createActiveUser(['email' => 'test@example.com']);

        $this->postJson('/api/user/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $record = PasswordReset::where('tokenable_id', $user->id)->first();
        $this->assertNotNull($record);
        // Token should be hashed (bcrypt starts with $2y$)
        $this->assertTrue(str_starts_with($record->token, '$2y$'));
        $this->assertEquals(0, $record->attempts);
    }

    // ─── Forgot Password: Rate Limiting ─────────────────────

    public function test_forgot_password_rate_limit_per_minute()
    {
        Mail::fake();
        $this->createActiveUser(['email' => 'test@example.com']);

        $limit = config('auth.otp_rate_limit.forgot_password_per_minute', 5);

        // Send requests up to limit (should succeed)
        for ($i = 0; $i < $limit; $i++) {
            $response = $this->postJson('/api/user/auth/forgot-password', [
                'email' => 'test@example.com',
            ]);
            $response->assertStatus(200);
        }

        // 4th request should be rate limited
        $response = $this->postJson('/api/user/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(429);
    }

    // ─── Reset Password: Success ────────────────────────────

    public function test_reset_password_success()
    {
        $user = $this->createActiveUser(['email' => 'test@example.com']);
        $plainOtp = '123456';
        $this->createOtpRecord($user, $plainOtp);

        $response = $this->postJson('/api/user/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => $plainOtp,
            'password' => 'NewPass@123',
            'password_confirmation' => 'NewPass@123',
        ]);

        $response->assertStatus(200);

        // Password should be updated
        $user->refresh();
        $this->assertTrue(Hash::check('NewPass@123', $user->password));

        // OTP record should be deleted
        $this->assertDatabaseMissing('t_password_resets', [
            'tokenable_id' => $user->id,
        ]);
    }

    // ─── Reset Password: Wrong OTP ──────────────────────────

    public function test_reset_password_wrong_otp_increments_attempts()
    {
        $user = $this->createActiveUser(['email' => 'test@example.com']);
        $this->createOtpRecord($user, '123456');

        $response = $this->postJson('/api/user/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => '999999',
            'password' => 'NewPass@123',
            'password_confirmation' => 'NewPass@123',
        ]);

        $response->assertStatus(400);

        // Attempts should be incremented
        $record = PasswordReset::where('tokenable_id', $user->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals(1, $record->attempts);
    }

    // ─── Reset Password: Max Attempts ───────────────────────

    public function test_reset_password_invalidates_otp_after_max_wrong_attempts()
    {
        $user = $this->createActiveUser(['email' => 'test@example.com']);
        $maxWrongAttempts = config('auth.otp_rate_limit.max_wrong_attempts', 5);
        $this->createOtpRecord($user, '123456', ['attempts' => $maxWrongAttempts]);

        $response = $this->postJson('/api/user/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => '999999',
            'password' => 'NewPass@123',
            'password_confirmation' => 'NewPass@123',
        ]);

        $response->assertStatus(429);

        // OTP record should be deleted (invalidated)
        $this->assertDatabaseMissing('t_password_resets', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_reset_password_correct_otp_still_fails_after_max_attempts()
    {
        $user = $this->createActiveUser(['email' => 'test@example.com']);
        $plainOtp = '123456';
        $maxWrongAttempts = config('auth.otp_rate_limit.max_wrong_attempts', 5);
        $this->createOtpRecord($user, $plainOtp, ['attempts' => $maxWrongAttempts]);

        $response = $this->postJson('/api/user/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => $plainOtp,
            'password' => 'NewPass@123',
            'password_confirmation' => 'NewPass@123',
        ]);

        $response->assertStatus(429);
    }

    // ─── Reset Password: OTP Expired ────────────────────────

    public function test_reset_password_expired_otp()
    {
        $user = $this->createActiveUser(['email' => 'test@example.com']);
        $plainOtp = '123456';
        $this->createOtpRecord($user, $plainOtp, [
            'expires_at' => now()->subMinutes(10),
        ]);

        $response = $this->postJson('/api/user/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => $plainOtp,
            'password' => 'NewPass@123',
            'password_confirmation' => 'NewPass@123',
        ]);

        $response->assertStatus(400);
    }

    // ─── Reset Password: Rate Limiting ──────────────────────

    public function test_reset_password_rate_limit()
    {
        $user = $this->createActiveUser(['email' => 'test@example.com']);
        $plainOtp = '123456';
        $limit = config('auth.otp_rate_limit.reset_password_per_minute', 5);

        // Send requests up to limit with wrong OTP
        for ($i = 0; $i < $limit; $i++) {
            $this->createOtpRecord($user, $plainOtp);
            $this->postJson('/api/user/auth/reset-password', [
                'email' => 'test@example.com',
                'token' => '999999',
                'password' => 'NewPass@123',
                'password_confirmation' => 'NewPass@123',
            ]);
        }

        // Re-create OTP and try again - should be rate limited
        $this->createOtpRecord($user, $plainOtp);
        $response = $this->postJson('/api/user/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => $plainOtp,
            'password' => 'NewPass@123',
            'password_confirmation' => 'NewPass@123',
        ]);

        $response->assertStatus(429);
    }

    // ─── Reset Password: Validation ─────────────────────────

    public function test_reset_password_validation_missing_fields()
    {
        $response = $this->postJson('/api/user/auth/reset-password', []);

        $response->assertStatus(422);
    }

    public function test_reset_password_validation_weak_password()
    {
        $user = $this->createActiveUser(['email' => 'test@example.com']);

        $response = $this->postJson('/api/user/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => '123456',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_password_no_otp_record()
    {
        $user = $this->createActiveUser(['email' => 'test@example.com']);

        $response = $this->postJson('/api/user/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => '123456',
            'password' => 'NewPass@123',
            'password_confirmation' => 'NewPass@123',
        ]);

        $response->assertStatus(400);
    }
}
