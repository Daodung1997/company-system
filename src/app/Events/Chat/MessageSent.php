<?php

namespace App\Events\Chat;

use App\Http\Resources\Chat\MessageResource;
use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        // Resolve resource to array for consistent payload
        $this->message = (new MessageResource($message))->resolve();
    }

    public function broadcastOn()
    {
        // Broadcast on private channel: conversation.{id}
        return new PrivateChannel('conversation.'.$this->message['conversation_id']);
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }
}
