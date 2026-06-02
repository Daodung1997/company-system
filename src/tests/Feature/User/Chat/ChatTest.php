<?php

namespace Tests\Feature\User\Chat;

use App\Constants\Commons\CommonRolesConst;
use App\Constants\Master\Models\Conversation\ConversationStatusConst;
use App\Constants\Master\Models\Conversation\ConversationTypeConst;
use App\Constants\Master\Models\Message\MessageTypeConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $customer;

    protected $worker;

    protected $outsider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create([
            'role' => CommonRolesConst::CUSTOMER,
            'status' => UserStatusConst::ACTIVE,
        ]);

        $this->worker = User::factory()->create([
            'role' => CommonRolesConst::WORKER,
            'status' => UserStatusConst::ACTIVE,
        ]);

        $this->outsider = User::factory()->create([
            'role' => CommonRolesConst::CUSTOMER,
            'status' => UserStatusConst::ACTIVE,
        ]);
    }

    /**
     * Helper: Create a conversation with participants.
     */
    protected function createConversation(?int $relatedId = null): Conversation
    {
        $conversation = Conversation::create([
            'type' => ConversationTypeConst::JOB,
            'related_id' => $relatedId,
            'creator_id' => $this->customer->id,
            'status' => ConversationStatusConst::ACTIVE,
            'last_message_at' => now(),
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $this->customer->id,
            'is_read' => true,
            'last_read_at' => now(),
            'is_muted' => false,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $this->worker->id,
            'is_read' => true,
            'last_read_at' => now(),
            'is_muted' => false,
        ]);

        return $conversation;
    }

    // ----------------------------------------------------------------------
    // List Conversations
    // ----------------------------------------------------------------------

    public function test_list_conversations_success()
    {
        $conversation = $this->createConversation(100);

        $response = $this->actingAs($this->customer, 'api')
            ->getJson('/api/user/chat/conversations');

        $response->assertStatus(200)
            ->assertJsonPath('code', 200)
            ->assertJsonCount(1, 'data.data');
    }

    public function test_list_conversations_empty_for_outsider()
    {
        $this->createConversation(100);

        $response = $this->actingAs($this->outsider, 'api')
            ->getJson('/api/user/chat/conversations');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.data');
    }

    public function test_list_conversations_unauthenticated_401()
    {
        $response = $this->getJson('/api/user/chat/conversations');

        $response->assertStatus(401);
    }

    // ----------------------------------------------------------------------
    // Start Conversation
    // ----------------------------------------------------------------------

    public function test_start_conversation_success()
    {
        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/user/chat/conversations/start', [
                'user_ids' => [$this->worker->id],
                'type' => ConversationTypeConst::JOB,
                'related_id' => 999,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.type', ConversationTypeConst::JOB)
            ->assertJsonPath('data.related_id', 999);

        $this->assertDatabaseHas('t_conversations', [
            'type' => ConversationTypeConst::JOB,
            'related_id' => 999,
            'creator_id' => $this->customer->id,
        ]);

        // Both participants created
        $conversationId = $response->json('data.id');
        $this->assertDatabaseHas('t_conversation_participants', [
            'conversation_id' => $conversationId,
            'user_id' => $this->customer->id,
        ]);
        $this->assertDatabaseHas('t_conversation_participants', [
            'conversation_id' => $conversationId,
            'user_id' => $this->worker->id,
        ]);
    }

    public function test_start_conversation_returns_existing()
    {
        $existing = $this->createConversation(500);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/user/chat/conversations/start', [
                'user_ids' => [$this->worker->id],
                'type' => ConversationTypeConst::JOB,
                'related_id' => 500,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $existing->id);

        // No new conversation created
        $this->assertEquals(1, Conversation::count());
    }

    public function test_start_conversation_validation_422()
    {
        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/user/chat/conversations/start', []);

        $response->assertStatus(422);
    }

    // ----------------------------------------------------------------------
    // Send Message
    // ----------------------------------------------------------------------

    public function test_send_message_success()
    {
        $conversation = $this->createConversation();

        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/user/chat/conversations/{$conversation->id}/messages", [
                'content' => 'Hello Worker!',
                'type' => MessageTypeConst::TEXT,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.content', 'Hello Worker!')
            ->assertJsonPath('data.type', MessageTypeConst::TEXT);

        $this->assertDatabaseHas('t_messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $this->customer->id,
            'content' => 'Hello Worker!',
            'type' => MessageTypeConst::TEXT,
        ]);

        // Worker should be marked as unread
        $this->assertDatabaseHas('t_conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $this->worker->id,
            'is_read' => false,
        ]);

        // Sender should be marked as read
        $this->assertDatabaseHas('t_conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $this->customer->id,
            'is_read' => true,
        ]);
    }

    public function test_send_message_non_participant_403()
    {
        $conversation = $this->createConversation();

        $response = $this->actingAs($this->outsider, 'api')
            ->postJson("/api/user/chat/conversations/{$conversation->id}/messages", [
                'content' => 'I should not be here',
                'type' => MessageTypeConst::TEXT,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('messages.error_code', 'CHAT_001');

        $this->assertDatabaseMissing('t_messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $this->outsider->id,
        ]);
    }

    // ----------------------------------------------------------------------
    // List Messages
    // ----------------------------------------------------------------------

    public function test_list_messages_success()
    {
        $conversation = $this->createConversation();

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->customer->id,
            'content' => 'Message 1',
            'type' => MessageTypeConst::TEXT,
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->worker->id,
            'content' => 'Message 2',
            'type' => MessageTypeConst::TEXT,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->getJson("/api/user/chat/conversations/{$conversation->id}/messages");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.data');
    }

    public function test_list_messages_non_participant_403()
    {
        $conversation = $this->createConversation();

        $response = $this->actingAs($this->outsider, 'api')
            ->getJson("/api/user/chat/conversations/{$conversation->id}/messages");

        $response->assertStatus(403)
            ->assertJsonPath('messages.error_code', 'CHAT_001');
    }

    // ----------------------------------------------------------------------
    // Mark Read
    // ----------------------------------------------------------------------

    public function test_mark_read_success()
    {
        $conversation = $this->createConversation();

        // Mark worker as unread first
        ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $this->worker->id)
            ->update(['is_read' => false]);

        $response = $this->actingAs($this->worker, 'api')
            ->putJson("/api/user/chat/conversations/{$conversation->id}/read");

        $response->assertStatus(200);

        $this->assertDatabaseHas('t_conversation_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $this->worker->id,
            'is_read' => true,
        ]);
    }

    // ----------------------------------------------------------------------
    // Upload Media
    // ----------------------------------------------------------------------

    public function test_upload_media_success()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('photo.jpg', 100, 100)->size(1024);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/user/chat/media', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['url']]);
    }

    public function test_upload_media_validation_422()
    {
        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/user/chat/media', []);

        $response->assertStatus(422);
    }
}
