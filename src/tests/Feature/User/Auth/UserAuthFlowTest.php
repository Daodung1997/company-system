<?php

namespace Tests\Feature\User\Auth;

use App\Constants\Master\Models\User\UserRoleConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Constants\Master\Models\UserVerification\UserVerificationTypeConst;
use App\Constants\Master\Models\WorkerProfile\WorkerProfileStatus;
use App\Models\User;
use App\Models\UserVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createVerifiedUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'status' => UserStatusConst::ACTIVE,
            'email_verified_at' => now(),
            'role' => null,
            'password' => Hash::make('Test@123'),
        ], $overrides));
    }

    private function createPendingUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'status' => UserStatusConst::PENDING_VERIFICATION,
            'email_verified_at' => null,
            'role' => null,
            'password' => Hash::make('Test@123'),
        ], $overrides));
    }

    // ─── Register ────────────────────────────────────

    public function test_register_success_without_role()
    {
        Mail::fake();

        $response = $this->postJson('/api/user/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Test@123',
            'password_confirmation' => 'Test@123',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('m_users', [
            'email' => 'test@example.com',
            'role' => null,
            'status' => UserStatusConst::PENDING_VERIFICATION,
        ]);

        // OTP record should be created in m_user_verifications
        $user = User::where('email', 'test@example.com')->first();
        $this->assertDatabaseHas('m_user_verifications', [
            'user_id' => $user->id,
            'type' => UserVerificationTypeConst::REGISTER,
        ]);
    }

    public function test_register_rejects_role_field()
    {
        Mail::fake();

        $response = $this->postJson('/api/user/auth/register', [
            'name' => 'Test User',
            'email' => 'test2@example.com',
            'password' => 'Test@123',
            'password_confirmation' => 'Test@123',
            'role' => 'customer',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('m_users', [
            'email' => 'test2@example.com',
            'role' => null,
        ]);
    }

    // ─── Verify Email OTP ────────────────────────────

    public function test_verify_email_otp_success()
    {
        $user = $this->createPendingUser();
        $otp = '123456';

        UserVerification::create([
            'user_id' => $user->id,
            'token' => $otp,
            'type' => UserVerificationTypeConst::REGISTER,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/user/auth/verify-email', [
            'email' => $user->email,
            'otp' => $otp,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('m_users', [
            'id' => $user->id,
            'status' => UserStatusConst::ACTIVE,
        ]);

        // OTP should be deleted
        $this->assertDatabaseMissing('m_user_verifications', [
            'user_id' => $user->id,
            'token' => $otp,
            'type' => UserVerificationTypeConst::REGISTER,
        ]);
    }

    public function test_verify_email_otp_invalid()
    {
        $user = $this->createPendingUser();

        UserVerification::create([
            'user_id' => $user->id,
            'token' => '123456',
            'type' => UserVerificationTypeConst::REGISTER,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/user/auth/verify-email', [
            'email' => $user->email,
            'otp' => '999999',
        ]);

        $response->assertStatus(400);
    }

    public function test_verify_email_otp_expired()
    {
        $user = $this->createPendingUser();

        UserVerification::create([
            'user_id' => $user->id,
            'token' => '123456',
            'type' => UserVerificationTypeConst::REGISTER,
            'expires_at' => now()->subMinutes(5),
        ]);

        $response = $this->postJson('/api/user/auth/verify-email', [
            'email' => $user->email,
            'otp' => '123456',
        ]);

        $response->assertStatus(400);
    }

    public function test_verify_email_already_verified()
    {
        $user = $this->createVerifiedUser();

        $response = $this->postJson('/api/user/auth/verify-email', [
            'email' => $user->email,
            'otp' => '123456',
        ]);

        $response->assertStatus(400);
    }

    // ─── Resend Verification OTP ─────────────────────

    public function test_resend_verification_otp_success()
    {
        Mail::fake();
        $user = $this->createPendingUser();

        $response = $this->postJson('/api/user/auth/resend-verification-otp', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('m_user_verifications', [
            'user_id' => $user->id,
            'type' => UserVerificationTypeConst::REGISTER,
        ]);
    }

    public function test_resend_verification_otp_already_verified()
    {
        $user = $this->createVerifiedUser();

        $response = $this->postJson('/api/user/auth/resend-verification-otp', [
            'email' => $user->email,
        ]);

        $response->assertStatus(400);
    }

    // ─── Login ───────────────────────────────────────

    public function test_login_user_without_role_returns_needs_role_selection_true()
    {
        $user = $this->createVerifiedUser();

        $response = $this->postJson('/api/user/auth/login', [
            'email' => $user->email,
            'password' => 'Test@123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.needs_role_selection', true)
            ->assertJsonPath('data.needs_profile_setup', true)
            ->assertJsonPath('data.user.roles', [])
            ->assertJsonPath('data.user.user_role', null)
            ->assertJsonStructure(['data' => ['access_token', 'needs_role_selection', 'needs_profile_setup', 'user']]);
    }

    public function test_login_user_with_single_role_returns_needs_role_selection_false()
    {
        $user = $this->createVerifiedUser(['role' => UserRoleConst::CUSTOMER]);
        // Create customer profile to simulate single role ownership
        $user->customerProfile()->create(['phone' => '123456789']);

        $response = $this->postJson('/api/user/auth/login', [
            'email' => $user->email,
            'password' => 'Test@123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.needs_role_selection', false)
            ->assertJsonPath('data.user.roles', ['CUSTOMER'])
            ->assertJsonPath('data.user.user_role', 'CUSTOMER')
            ->assertJsonPath('data.user.is_profile_completed', true);
    }

    public function test_login_user_with_multiple_roles_returns_needs_role_selection_true()
    {
        $user = $this->createVerifiedUser(['role' => UserRoleConst::CUSTOMER]);
        $user->customerProfile()->create(['phone' => '123456789']);
        $user->workerProfile()->create(['profile_status' => WorkerProfileStatus::PENDING]);

        $response = $this->postJson('/api/user/auth/login', [
            'email' => $user->email,
            'password' => 'Test@123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.needs_role_selection', true)
            ->assertJsonPath('data.user.roles', ['CUSTOMER', 'WORKER'])
            ->assertJsonPath('data.user.worker_status', 'PENDING');
    }

    // ─── Choose Role ─────────────────────────────────

    public function test_choose_role_customer_incomplete_profile_redirects_to_profile()
    {
        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/user/auth/choose-role', [
                'role' => 'customer',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.needs_role_selection', false)
            ->assertJsonPath('data.redirect_to', 'profile')
            ->assertJsonStructure(['data' => ['access_token', 'redirect_to', 'user']]);

        $this->assertDatabaseHas('m_users', [
            'id' => $user->id,
            'role' => UserRoleConst::CUSTOMER,
        ]);

        $this->assertDatabaseHas('m_customer_profiles', [
            'user_id' => $user->id,
            'phone' => null,
        ]);
    }

    public function test_choose_role_customer_complete_profile_redirects_to_home()
    {
        $user = $this->createVerifiedUser(['role' => UserRoleConst::CUSTOMER]);
        $user->customerProfile()->create(['phone' => '0987654321']);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/user/auth/choose-role', [
                'role' => 'customer',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.redirect_to', 'home');
    }

    public function test_choose_role_worker_incomplete_profile_redirects_to_register()
    {
        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/user/auth/choose-role', [
                'role' => 'worker',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.redirect_to', 'register')
            ->assertJsonPath('data.user.worker_status', 'INCOMPLETE');

        $this->assertDatabaseHas('m_users', [
            'id' => $user->id,
            'role' => UserRoleConst::WORKER,
        ]);

        $this->assertDatabaseHas('m_worker_profiles', [
            'user_id' => $user->id,
            'profile_status' => WorkerProfileStatus::INCOMPLETE,
        ]);
    }

    public function test_choose_role_worker_pending_profile_redirects_to_pending_screen()
    {
        $user = $this->createVerifiedUser(['role' => UserRoleConst::WORKER]);
        $user->workerProfile()->create(['profile_status' => WorkerProfileStatus::PENDING]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/user/auth/choose-role', [
                'role' => 'worker',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.redirect_to', 'pending_screen');
    }

    public function test_choose_role_worker_approved_profile_redirects_to_home()
    {
        $user = $this->createVerifiedUser(['role' => UserRoleConst::WORKER]);
        $user->workerProfile()->create(['profile_status' => WorkerProfileStatus::APPROVED]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/user/auth/choose-role', [
                'role' => 'worker',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.redirect_to', 'home');
    }

    public function test_choose_role_allows_switching_role_if_already_assigned()
    {
        $user = $this->createVerifiedUser(['role' => UserRoleConst::CUSTOMER]);
        $user->customerProfile()->create(['phone' => '123456789']);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/user/auth/choose-role', [
                'role' => 'worker',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.user_role', 'WORKER');

        $this->assertDatabaseHas('m_users', [
            'id' => $user->id,
            'role' => UserRoleConst::WORKER,
        ]);
    }

    public function test_choose_role_fails_with_invalid_role()
    {
        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/user/auth/choose-role', [
                'role' => 'admin',
            ]);

        $response->assertStatus(422);
    }

    public function test_choose_role_fails_unauthenticated()
    {
        $response = $this->postJson('/api/user/auth/choose-role', [
            'role' => 'customer',
        ]);

        $response->assertStatus(401);
    }
}
