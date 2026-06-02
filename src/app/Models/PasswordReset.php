<?php

namespace App\Models;

use App\Constants\Commons\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PasswordReset extends Model
{
    public const TABLE_NAME = 't_password_resets';

    protected $table = self::TABLE_NAME;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tokenable_id',
        'tokenable_type',
        'token',
        'attempts',
        'expires_at',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => Rule::DATE_TIME,
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the parent tokenable model (user or customer).
     */
    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Determine if the token has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
