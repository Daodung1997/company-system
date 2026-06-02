<?php

use App\Models\ConversationParticipant;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('conversation.{id}', function ($user, $id) {
    // Check if user is a participant of the conversation
    // Make sure to use integer id for comparison if strictly needed, usually implicit
    return ConversationParticipant::where('conversation_id', $id)
        ->where('user_id', $user->id)
        ->exists();
});
