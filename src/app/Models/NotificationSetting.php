<?php

namespace App\Models;

class NotificationSetting extends BaseModel
{
    public const TABLE_NAME = 'm_notification_settings';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'user_id',
        'type',
        'channel',
        'enabled',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
