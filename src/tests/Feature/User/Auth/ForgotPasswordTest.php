<?php

namespace Tests\Feature\User\Auth;

use App\Mail\ForgotPasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_forgot_password()
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/user/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('t_password_resets', [
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
        ]);

        Mail::assertQueued(ForgotPasswordMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_forgot_password_unified_response_for_non_existent_email()
    {
        Mail::fake();

        $response = $this->postJson('/api/user/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'code' => 200,
            'data' => [
                'message' => 'Nếu email tồn tại trong hệ thống, mã OTP đã được gửi.',
            ],
        ]);

        $this->assertDatabaseMissing('t_password_resets', [
            'tokenable_type' => 'App\Models\User',
        ]);

        Mail::assertNothingQueued();
    }

    public function test_user_can_reset_password_with_valid_token()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $token = '123456';

        DB::table('t_password_resets')->insert([
            'tokenable_id' => $user->id,
            'tokenable_type' => get_class($user),
            'token' => Hash::make($token),
            'created_at' => now(),
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/user/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => $token,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(200);

        // Verify password changed
        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));

        // Verify token deleted
        $this->assertDatabaseMissing('t_password_resets', [
            'tokenable_id' => $user->id,
            'token' => $token,
        ]);
    }
}
