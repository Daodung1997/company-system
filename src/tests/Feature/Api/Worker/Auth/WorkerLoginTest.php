<?php

namespace Tests\Feature\Api\Worker\Auth;

use App\Constants\Commons\CommonTokenConst;
use App\Constants\Master\Models\User\UserRoleConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WorkerLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the Passport /oauth/token endpoint
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'mock_access_token',
                'refresh_token' => 'mock_refresh_token',
                'expires_in' => 3600,
            ], 200),
        ]);
    }

    private function createWorker(string $status = UserStatusConst::ACTIVE, $profileStatus = 'approved'): User
    {
        $user = User::factory()->create([
            'email' => 'worker@test.com',
            'password' => Hash::make('Password@123'),
            'role' => UserRoleConst::WORKER,
            'status' => $status,
        ]);

        if ($profileStatus) {
            $user->workerProfile()->create([
                'phone' => '0123456789',
                'profile_status' => $profileStatus,
            ]);
        }

        return $user;
    }

    public function test_worker_can_login_successfully()
    {
        $user = $this->createWorker();

        $response = $this->postJson('/api/worker/auth/login', [
            'email' => 'worker@test.com',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    CommonTokenConst::ACCESS_TOKEN,
                    CommonTokenConst::TOKEN_TYPE,
                    CommonTokenConst::EXPIRES_IN,
                    CommonTokenConst::REFRESH_TOKEN,
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'status',
                        'profile_status',
                    ],
                ],
            ])
            ->assertJsonPath('data.user.role', UserRoleConst::WORKER)
            ->assertJsonPath('data.user.profile_status', 'approved');
    }

    public function test_login_fails_with_invalid_credentials()
    {
        $this->createWorker();

        $response = $this->postJson('/api/worker/auth/login', [
            'email' => 'worker@test.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('messages.message', 'Invalid credentials');
    }

    public function test_login_fails_for_customer_using_worker_endpoint()
    {
        $customer = clone $this->createWorker();
        $customer->role = UserRoleConst::CUSTOMER;
        $customer->save();

        $response = $this->postJson('/api/worker/auth/login', [
            'email' => 'worker@test.com',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('messages.message', 'Unauthorized access: Only workers are allowed.');
    }

    public function test_login_fails_for_blocked_worker()
    {
        $this->createWorker(UserStatusConst::BLOCKED);

        $response = $this->postJson('/api/worker/auth/login', [
            'email' => 'worker@test.com',
            'password' => 'Password@123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('messages.message', 'Account blocked');
    }

    public function test_login_validation_errors()
    {
        $response = $this->postJson('/api/worker/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['code', 'messages']);

        $messages = $response->json('messages');
        $this->assertTrue(in_array('email.required', $messages));
    }
}
