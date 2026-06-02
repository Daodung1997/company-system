<?php

namespace App\Models;

class UserVerification extends BaseModel
{
    public const TABLE_NAME = 'm_user_verifications';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'user_id',
        'token',
        'type',
        'expires_at',
        'created_by',
        'updated_by',
    ];
}
