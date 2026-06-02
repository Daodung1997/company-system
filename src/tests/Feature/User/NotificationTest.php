<?php

namespace Tests\Feature\User;

use App\Constants\Master\Models\Notification\NotificationTypeConst;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Firebase\FCMService;
use App\Services\User\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $fcmServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Mock FCMService
        $this->fcmServiceMock = Mockery::mock(FCMService::class);
        $this->app->instance(FCMService::class, $this->fcmServiceMock);
    }

    public function test_send_notification_successfully()
    {
        // Arrange
        $device = UserDevice::create([
            'user_id' => $this->user->id,
            'device_id' => 'device_123',
            'device_name' => 'Test Device',
            'fcm_token' => 'token_123',
            'device_type' => 'android',
        ]);

        $this->fcmServiceMock->shouldReceive('sendMulticast')
            ->once()
            ->with(['token_123'], 'Test Title', 'Test Body', Mockery::any());

        // Act
        $service = $this->app->make(NotificationService::class);
        $result = $service->sendNotification(
            $this->user->id,
            NotificationTypeConst::SYSTEM,
            'Test Title',
            'Test Body'
        );

        // Assert
        $this->assertDatabaseHas('t_notifications', [
            'user_id' => $this->user->id,
            'title' => 'Test Title',
            'type' => NotificationTypeConst::SYSTEM,
        ]);

        $this->assertNotNull($result);
    }

    public function test_list_notifications()
    {
        // Arrange
        Notification::create([
            'user_id' => $this->user->id,
            'type' => NotificationTypeConst::SYSTEM,
            'title' => 'Notif 1',
            'body' => 'Body 1',
            'created_at' => now()->subMinute(),
        ]);

        Notification::create([
            'user_id' => $this->user->id,
            'type' => NotificationTypeConst::SYSTEM,
            'title' => 'Notif 2',
            'body' => 'Body 2',
            'created_at' => now(),
        ]);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/user/notifications');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    'data' => [
                        '*' => ['id', 'title', 'body', 'is_read'],
                    ],
                    'metadata' => [
                        'unread_count',
                    ],
                ],
            ])
            ->assertJsonPath('data.metadata.unread_count', 2)
            ->assertJsonPath('data.data.0.title', 'Notif 2');
    }

    public function test_mark_as_read()
    {
        // Arrange
        $notif = Notification::create([
            'user_id' => $this->user->id,
            'type' => NotificationTypeConst::SYSTEM,
            'title' => 'Notif 1',
            'body' => 'Body 1',
        ]);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/user/notifications/{$notif->id}/read");

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('t_notifications', [
            'id' => $notif->id,
            'read_at' => now(), // checking it's not null effectively
        ]);

        $notif->refresh();
        $this->assertNotNull($notif->read_at);
    }

    public function test_mark_all_as_read()
    {
        // Arrange
        Notification::create(['user_id' => $this->user->id, 'title' => '1', 'body' => '1', 'type' => 'system']);
        Notification::create(['user_id' => $this->user->id, 'title' => '2', 'body' => '2', 'type' => 'system']);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->putJson('/api/user/notifications/read-all');

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseMissing('t_notifications', [
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);
    }

    public function test_cannot_mark_others_notification_as_read()
    {
        // Arrange
        $otherUser = User::factory()->create();
        $notif = Notification::create([
            'user_id' => $otherUser->id,
            'type' => NotificationTypeConst::SYSTEM,
            'title' => 'Other Notif',
            'body' => 'Body',
        ]);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/user/notifications/{$notif->id}/read");

        // Assert: Currently implementation might return 200 but not update, or we might want 403.
        // Given current fix, updateWhere returns 0 (false-ish) but doesn't throw.
        // However, the controller returns success regardless of update count unless we throw explicit exception.
        // Let's check DB hasn't changed.

        $response->assertStatus(200); // Or 403 if we enforced policy, but here we enforce logic.

        $this->assertDatabaseHas('t_notifications', [
            'id' => $notif->id,
            'read_at' => null,
        ]);
    }

    public function test_notification_returns_empty_data_as_object()
    {
        // Arrange
        Notification::create([
            'user_id' => $this->user->id,
            'type' => NotificationTypeConst::SYSTEM,
            'title' => 'Notif 1',
            'body' => 'Body 1',
            'data' => null, // empty data
        ]);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/user/notifications');

        // Assert
        $response->assertStatus(200);
        
        // Assert that the 'data' field is encoded as an empty JSON object {}
        $this->assertStringContainsString('"data":{}', $response->getContent());
    }

    public function test_delete_notifications_selectively_successfully()
    {
        // Arrange
        $notif1 = Notification::create(['user_id' => $this->user->id, 'title' => '1', 'body' => '1', 'type' => 'system']);
        $notif2 = Notification::create(['user_id' => $this->user->id, 'title' => '2', 'body' => '2', 'type' => 'system']);
        $notif3 = Notification::create(['user_id' => $this->user->id, 'title' => '3', 'body' => '3', 'type' => 'system']);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->deleteJson('/api/user/notifications', [
                'ids' => [$notif1->id, $notif2->id]
            ]);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseMissing('t_notifications', ['id' => $notif1->id]);
        $this->assertDatabaseMissing('t_notifications', ['id' => $notif2->id]);
        $this->assertDatabaseHas('t_notifications', ['id' => $notif3->id]);
    }

    public function test_delete_all_notifications_successfully()
    {
        // Arrange
        $notif1 = Notification::create(['user_id' => $this->user->id, 'title' => '1', 'body' => '1', 'type' => 'system']);
        $notif2 = Notification::create(['user_id' => $this->user->id, 'title' => '2', 'body' => '2', 'type' => 'system']);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->deleteJson('/api/user/notifications');

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseMissing('t_notifications', ['user_id' => $this->user->id]);
    }

    public function test_delete_notifications_prevents_idor()
    {
        // Arrange
        $otherUser = User::factory()->create();
        $otherNotif = Notification::create(['user_id' => $otherUser->id, 'title' => 'Other', 'body' => 'Other', 'type' => 'system']);
        $myNotif = Notification::create(['user_id' => $this->user->id, 'title' => 'Mine', 'body' => 'Mine', 'type' => 'system']);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->deleteJson('/api/user/notifications', [
                'ids' => [$otherNotif->id, $myNotif->id]
            ]);

        // Assert
        $response->assertStatus(200);

        // myNotif should be deleted, otherNotif should NOT be deleted
        $this->assertDatabaseMissing('t_notifications', ['id' => $myNotif->id]);
        $this->assertDatabaseHas('t_notifications', ['id' => $otherNotif->id]);
    }

    public function test_delete_notifications_validation_fails()
    {
        // Act
        $response = $this->actingAs($this->user, 'api')
            ->deleteJson('/api/user/notifications', [
                'ids' => 'not-an-array'
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonStructure(['code', 'messages']);
    }
}
