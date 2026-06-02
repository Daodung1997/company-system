<?php

namespace Tests\Feature\Admin\Auth;

use App\Constants\Master\Models\Admin\AdminStatusConst;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login()
    {
        $admin = Admin::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('SecurePass@123'),
            'status' => AdminStatusConst::ACTIVE,
        ]);

        $response = $this->postJson('/api/admin/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'SecurePass@123',
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

    public function test_inactive_admin_cannot_login()
    {
        $admin = Admin::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('SecurePass@123'),
            'status' => AdminStatusConst::INACTIVE,
        ]);

        $response = $this->postJson('/api/admin/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'SecurePass@123',
        ]);

        $response->assertStatus(403);
    }
}
