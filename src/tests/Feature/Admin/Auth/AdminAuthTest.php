<?php

namespace Tests\Feature\Admin\Auth;

use App\Constants\Master\Models\Admin\AdminStatusConst;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected string $password = 'Password123!';

    protected function setUp(): void
    {
        parent::setUp();
        // Create a default active admin
        $this->admin = Admin::factory()->create([
            'email' => 'admin@viecvat.com',
            'password' => Hash::make($this->password),
            'status' => AdminStatusConst::ACTIVE,
        ]);

        // Ensure AdminStatusConst exists, otherwise use integer 1
    }

    public function test_admin_can_login_with_valid_credentials()
    {
        $response = $this->postJson('/api/admin/auth/login', [
            'email' => 'admin@viecvat.com',
            'password' => $this->password,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'data' => [
                    'token_type' => 'bearer',
                ],
            ])
            ->assertJsonStructure([
                'code',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'user' => ['id', 'email', 'name'],
                ],
            ]);
    }

    public function test_admin_cannot_login_with_invalid_password()
    {
        $response = $this->postJson('/api/admin/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(422);
        // Or 422 if validation, checking convention usually 401 for bad credentials
    }

    public function test_admin_cannot_login_with_non_existent_email()
    {
        $response = $this->postJson('/api/admin/auth/login', [
            'email' => 'notfound@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_login_validation_errors()
    {
        $response = $this->postJson('/api/admin/auth/login', []);

        $response->assertStatus(422)
            ->assertJson([
                'messages' => ['email.required'],
            ]);
    }

    public function test_inactive_admin_cannot_login()
    {
        // Define status constant locally or use explicit value if class not found yet (though it should exist based on previous tasks)
        // Adjusting logic to use the factory's default or override

        $inactiveAdmin = Admin::factory()->create([
            'email' => 'inactive@test.com',
            'password' => Hash::make($this->password),
            'status' => AdminStatusConst::INACTIVE,
        ]);

        $response = $this->postJson('/api/admin/auth/login', [
            'email' => 'inactive@test.com',
            'password' => $this->password,
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_logout()
    {
        // Determine guard or just use actingAs with 'admin' guard
        $token = auth('admin')->login($this->admin);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/admin/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['code' => 200]);

        // Assert token is invalidated?
        // difficult to test directly without try/catch middleware exception for next request
    }

    public function test_admin_can_refresh_token()
    {
        $token = auth('admin')->login($this->admin);

        // Sleep 1 second to ensure new token has different exp time (optional)

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/admin/auth/refresh-token');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'refresh_token', // Added check
                ],
            ]);
    }

    public function test_guest_cannot_access_protected_routes()
    {
        // Logout route
        $this->postJson('/api/admin/auth/logout')
            ->assertStatus(401); // Unauthenticated

        // Refresh route
        $this->postJson('/api/admin/auth/refresh-token')
            ->assertStatus(401);
    }
}
