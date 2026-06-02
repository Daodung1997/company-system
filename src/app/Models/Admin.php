<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;

class Admin extends BaseAuthenticateModel
{
    use HasFactory, HasRoles;

    public const TABLE_NAME = 'm_admins';

    public const PREFIX_CODE = 'A';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'code',
        'name',
        'email',
        'password',
        'status',
        'avatar_url',
        'last_login_at',
        'created_by',
        'updated_by',
    ];

    protected $hidden = ['password'];
}
