<?php

namespace Tests\Feature\User\Notification;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterDeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $endpoint = '/api/user/notifications/device-token';

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_register_device_token_successfully()
    {
        $payload = [
            'fcm_token' => 'test-token-123',
            'device_id' => 'device-uuid-1',
            'device_type' => 'ios',
            'device_name' => 'iPhone 15',
        ];

        $response = $this->actingAs($this->user)
            ->postJson($this->endpoint, $payload);

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'data' => [
                    'message' => 'Device token registered successfully',
                ],
            ]);

        $this->assertDatabaseHas('t_user_devices', [
            'user_id' => $this->user->id,
            'device_id' => 'device-uuid-1',
            'fcm_token' => 'test-token-123',
            'device_type' => 'ios',
        ]);
    }

    public function test_can_update_device_token_for_existing_device()
    {
        // Create existing device
        UserDevice::create([
            'user_id' => $this->user->id,
            'device_id' => 'device-uuid-1',
            'fcm_token' => 'old-token',
            'device_name' => 'Old Phone',
        ]);

        $payload = [
            'fcm_token' => 'new-token-456',
            'device_id' => 'device-uuid-1', // Same device ID
            'device_type' => 'android',
        ];

        $response = $this->actingAs($this->user)
            ->postJson($this->endpoint, $payload);

        $response->assertStatus(200);

        // Check DB updated
        $this->assertDatabaseHas('t_user_devices', [
            'user_id' => $this->user->id,
            'device_id' => 'device-uuid-1',
            'fcm_token' => 'new-token-456',
        ]);

        // Ensure no duplicate created
        $this->assertEquals(1, UserDevice::where('user_id', $this->user->id)->count());
    }

    public function test_validation_requires_fcm_token_and_device_id()
    {
        $response = $this->actingAs($this->user)
            ->postJson($this->endpoint, []);

        $response->assertStatus(422)
            ->assertJson([
                'code' => 422,
            ])
            ->assertJsonFragment(['fcm_token.required']);
    }

    public function test_guest_cannot_register_device_token()
    {
        $response = $this->postJson($this->endpoint, [
            'fcm_token' => 'token',
            'device_id' => 'id',
        ]);

        $response->assertStatus(401);
    }
}
