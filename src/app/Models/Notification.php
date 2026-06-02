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
        'data' => 'object',
        'read_at' => 'datetime',
    ];
}
