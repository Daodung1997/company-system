<?php

namespace App\Models;

class Message extends BaseModel
{
    public const TABLE_NAME = 't_messages';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'content',
        'type',
        'read_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function conversation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function sender(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
