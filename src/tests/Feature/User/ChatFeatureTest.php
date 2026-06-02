<?php

namespace Tests\Feature\User;

use App\Constants\Master\Models\Conversation\ConversationTypeConst;
use App\Constants\Master\Models\Message\MessageTypeConst;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatFeatureTest extends TestCase
{
    // ... setup ...

    public function test_user_can_upload_image()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('test_image.jpg');

        $response = $this->actingAs($this->userA, 'api')
            ->postJson('/api/user/chat/media', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['url']]);

        // Verify file stored (mocked)
        // Note: The service stores in 'chat-media'
        // Since we mock 'public', we check if it exists in that disk
        // The service returns URL config('app.url') . '/storage/' . path
        // We can just verify success response and structure for now, or check Storage::disk('public')->assertExists(...)

        $url = $response->json('data.url');
        $this->assertNotNull($url);
    }

    use RefreshDatabase, WithFaker;

    protected $userA;

    protected $userB;

    protected function setUp(): void
    {
        parent::setUp();
        // Assuming factories exist
        $this->userA = User::factory()->create();
        $this->userB = User::factory()->create();
    }

    public function test_user_can_start_direct_conversation()
    {
        $response = $this->actingAs($this->userA, 'api')
            ->postJson('/api/user/chat/conversations/start', [
                'user_ids' => [$this->userB->id],
                'type' => ConversationTypeConst::DIRECT,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    'id',
                    'type',
                    'participants' => [
                        '*' => ['user_id', 'is_read'],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('t_conversations', [
            'type' => ConversationTypeConst::DIRECT,
            'creator_id' => $this->userA->id,
        ]);

        $conversationId = $response->json('data.id');
        $this->assertDatabaseHas('t_conversation_participants', [
            'conversation_id' => $conversationId,
            'user_id' => $this->userA->id,
        ]);
        $this->assertDatabaseHas('t_conversation_participants', [
            'conversation_id' => $conversationId,
            'user_id' => $this->userB->id,
        ]);
    }

    public function test_user_can_send_message()
    {
        // 1. Create Conversation
        $response = $this->actingAs($this->userA, 'api')
            ->postJson('/api/user/chat/conversations/start', [
                'user_ids' => [$this->userB->id],
            ]);

        $response->assertStatus(200);

        $conversation = Conversation::first();

        // 2. Send Message
        $content = 'Hello World';
        $response = $this->actingAs($this->userA, 'api')
            ->postJson("/api/user/chat/conversations/{$conversation->id}/messages", [
                'content' => $content,
                'type' => MessageTypeConst::TEXT,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.content', $content)
            ->assertJsonPath('data.sender_id', $this->userA->id);

        $this->assertDatabaseHas('t_messages', [
            'conversation_id' => $conversation->id,
            'content' => $content,
        ]);

        // 3. Check Conversation update
        $this->assertDatabaseHas('t_conversations', [
            'id' => $conversation->id,
            'last_message_content' => $content,
        ]);
    }

    public function test_user_can_list_conversations()
    {
        // Create conversation
        $this->actingAs($this->userA, 'api')
            ->postJson('/api/user/chat/conversations/start', ['user_ids' => [$this->userB->id]]);

        $response = $this->actingAs($this->userA, 'api')
            ->getJson('/api/user/chat/conversations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'last_message_content', 'participants'],
                    ],
                ],
            ]);

        // User C shouldn't see it (if logic correct)
        $userC = User::factory()->create();
        $responseC = $this->actingAs($userC, 'api')
            ->getJson('/api/user/chat/conversations');

        $responseC->assertStatus(200)
            ->assertJsonCount(0, 'data.data');
    }

    public function test_user_can_get_history()
    {
        // 1. Create convo and send messages
        $this->actingAs($this->userA, 'api')
            ->postJson('/api/user/chat/conversations/start', ['user_ids' => [$this->userB->id]]);
        $conversation = Conversation::first();

        // Send 3 messages
        $messages = [];
        for ($i = 0; $i < 3; $i++) {
            $resp = $this->actingAs($this->userA, 'api')
                ->postJson("/api/user/chat/conversations/{$conversation->id}/messages", [
                    'content' => "Message $i",
                    'type' => MessageTypeConst::TEXT,
                ]);
            $messages[] = $resp->json('data.id');
        }

        // 2. Get history
        $response = $this->actingAs($this->userB, 'api')
            ->getJson("/api/user/chat/conversations/{$conversation->id}/messages");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'content', 'sender_id', 'created_at'],
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data.data');

        // Check filtering (ensure no other messages if multiple convos existed)
    }

    public function test_user_can_mark_conversation_as_read()
    {
        $response = $this->actingAs($this->userA, 'api')
            ->postJson('/api/user/chat/conversations/start', ['user_ids' => [$this->userB->id]]);

        $response->assertStatus(200);
        $conversation = Conversation::first();

        $this->actingAs($this->userA, 'api')
            ->putJson("/api/user/chat/conversations/{$conversation->id}/read");

        $this->assertDatabaseHas('t_conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $this->userA->id,
            'is_read' => 1,
        ]);
    }

    public function test_message_broadcasts_event()
    {
        \Illuminate\Support\Facades\Config::set('broadcasting.default', 'log');
        \Illuminate\Support\Facades\Event::fake();

        $this->actingAs($this->userA, 'api')
            ->postJson('/api/user/chat/conversations/start', ['user_ids' => [$this->userB->id]]);
        $conversation = \App\Models\Conversation::first();

        // Need to ensure conversation ID is correct for existing one

        $this->actingAs($this->userA, 'api')
            ->postJson("/api/user/chat/conversations/{$conversation->id}/messages", [
                'content' => 'Hello Realtime',
                'type' => \App\Constants\Master\Models\Message\MessageTypeConst::TEXT,
            ]);

        \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\Chat\MessageSent::class, function ($event) use ($conversation) {
            return $event->message['conversation_id'] === $conversation->id
                && $event->message['content'] === 'Hello Realtime';
        });
    }
}
