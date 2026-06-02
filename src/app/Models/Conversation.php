<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends BaseModel
{
    use HasFactory, SoftDeletes;

    public const TABLE_NAME = 't_conversations';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'type',
        'related_id',
        'creator_id',
        'status',
        'last_message_at',
        'last_message_content',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class, 'conversation_id');
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class, 'conversation_id')->latestOfMany();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 't_conversation_participants', 'conversation_id', 'user_id')
            ->withPivot(['is_read', 'last_read_at', 'is_muted'])
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
