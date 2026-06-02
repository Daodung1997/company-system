<?php

namespace Tests\Feature\User\Auth;

use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        Mail::fake();

        $response = $this->postJson('/api/user/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('m_users', [
            'email' => 'test@example.com',
            'status' => UserStatusConst::PENDING_VERIFICATION,
            'role' => null,
        ]);

        Mail::assertQueued(\App\Mail\VerifyEmail::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    }

    public function test_user_cannot_register_with_existing_email()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/user/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'messages' => ['email.unique'],
            ]);
    }
}
