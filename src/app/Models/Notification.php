<?php

namespace App\Models;

class Notification extends BaseModel
{
    public const TABLE_NAME = 't_notifications';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'type',
        'data',
        'read_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    protected $appends = ['action_url', 'is_read'];

    /**
     * Relationship: the user this notification belongs to.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Accessor: extract action_url from the data JSON field.
     */
    public function getActionUrlAttribute(): ?string
    {
        return $this->data['action_url'] ?? null;
    }

    /**
     * Accessor: whether the notification has been read.
     */
    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }
}
