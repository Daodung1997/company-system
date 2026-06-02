<?php

namespace Tests\Feature\User\Auth;

use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => UserStatusConst::ACTIVE,
        ]);

        $response = $this->postJson('/api/user/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'refresh_token',
                    'expires_in',
                    'user',
                ],
            ]);
    }

    public function test_pending_user_cannot_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => UserStatusConst::PENDING_VERIFICATION,
        ]);

        $response = $this->postJson('/api/user/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        if ($response->status() !== 403) {
            dump($response->json());
            dump($response->content());
        }

        $response->assertStatus(403);
    }
}
