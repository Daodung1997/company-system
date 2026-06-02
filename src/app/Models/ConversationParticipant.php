<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends BaseModel
{
    public const TABLE_NAME = 't_conversation_participants';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'is_read',
        'last_read_at',
        'is_muted',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_muted' => 'boolean',
        'last_read_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
