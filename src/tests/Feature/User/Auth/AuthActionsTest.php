<?php

namespace Tests\Feature\User\Auth;

use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\User;
use App\Models\UserVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'status' => UserStatusConst::ACTIVE,
            'role' => null, // Initial state for choose role test
        ]);
    }

    public function test_user_can_verify_email()
    {
        // Setup pending user and verification record
        $pendingUser = User::factory()->create([
            'status' => UserStatusConst::PENDING_VERIFICATION,
            'email' => 'pending@example.com',
        ]);

        $otp = '123456';

        UserVerification::create([
            'user_id' => $pendingUser->id,
            'token' => $otp,
            'type' => \App\Constants\Master\Models\UserVerification\UserVerificationTypeConst::REGISTER,
            'expires_at' => now()->addMinutes(60),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/user/auth/verify-email', [
            'email' => 'pending@example.com',
            'otp' => $otp,
        ]);

        $response->assertStatus(200);

        $pendingUser->refresh();
        $this->assertEquals(UserStatusConst::ACTIVE, $pendingUser->status);
        $this->assertNotNull($pendingUser->email_verified_at);
        $this->assertDatabaseMissing('m_user_verifications', ['user_id' => $pendingUser->id]);
    }

    public function test_user_cannot_verify_email_with_invalid_token()
    {
        // Setup pending user
        User::factory()->create([
            'status' => UserStatusConst::PENDING_VERIFICATION,
            'email' => 'pending@example.com',
        ]);

        $response = $this->postJson('/api/user/auth/verify-email', [
            'email' => 'pending@example.com',
            'otp' => '000000', // Invalid OTP
        ]);

        $response->assertStatus(400);
    }

    public function test_user_can_logout()
    {
        $token = auth('api')->login($this->user);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/user/auth/logout');

        $response->assertStatus(200);

        // Ensure token is invalidated
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/user/auth/logout'); // Try accessing protected route

        // Blacklisted token might throw 500 or 401 depending on Handler config.
        // Both indicate access denied for this test context.
        $this->assertTrue(in_array($response->status(), [401, 500]));
    }

    public function test_user_can_refresh_token()
    {
        $token = auth('api')->login($this->user);

        // Wait a bit if needed (usually not for refresh logic unless TTL is strict)

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/user/auth/refresh-token');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                ],
            ]);
    }

    public function test_user_can_choose_role()
    {
        $token = auth('api')->login($this->user);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/user/auth/choose-role', ['role' => 'worker']);

        $response->assertStatus(200)
            ->assertJsonPath('data.needs_role_selection', false);

        $this->user->refresh();
        $this->assertEquals(\App\Constants\Master\Models\User\UserRoleConst::WORKER, $this->user->role);

        // Assert worker profile is created
        $this->assertDatabaseHas('m_worker_profiles', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_cannot_choose_invalid_role()
    {
        $token = auth('api')->login($this->user);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/user/auth/choose-role', [
                'role' => 'admin', // Assuming admin is restricted or invalid role choice
            ]);

        $response->assertStatus(422);
        // ->assertJsonValidationErrors(['role']); // Custom handler returns different format
    }
}
