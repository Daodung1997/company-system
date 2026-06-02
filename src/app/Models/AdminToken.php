<?php

namespace App\Models;

class AdminToken extends BaseModel
{
    public const TABLE_NAME = 'm_admin_tokens';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'admin_id',
        'token',
        'expires_at',
        'created_by',
        'updated_by',
    ];
}
