<?php

namespace App\Models;

class UserToken extends BaseModel
{
    public const TABLE_NAME = 'm_user_tokens';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'user_id',
        'token',
        'device_id',
        'expires_at',
        'created_by',
        'updated_by',
    ];
}
