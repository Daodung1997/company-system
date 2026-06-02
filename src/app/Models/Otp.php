<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Otp Model
 *
 * @property int $id
 * @property string $identifier
 * @property string $code
 * @property string $type
 * @property \Carbon\Carbon $expires_at
 * @property bool $is_used
 */
class Otp extends BaseModel
{
    use SoftDeletes;

    protected $table = 't_otps';

    protected $fillable = [
        'identifier',
        'code',
        'type',
        'expires_at',
        'is_used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    /**
     * Check if OTP is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if OTP is valid (not expired and not used)
     */
    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->is_used;
    }

    /**
     * Mark OTP as used
     */
    public function markAsUsed(): bool
    {
        $this->is_used = true;

        return $this->save();
    }
}
