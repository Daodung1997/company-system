<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * UserDevice Model
 *
 * @property int $id
 * @property int $user_id
 * @property string $device_id
 * @property string $device_name
 * @property string|null $device_type
 * @property string|null $fcm_token
 * @property string|null $ip_address
 * @property \Carbon\Carbon $last_active_at
 */
class UserDevice extends BaseModel
{
    use SoftDeletes;

    protected $table = 't_user_devices';

    protected $fillable = [
        'user_id',
        'device_id',
        'device_name',
        'device_type',
        'fcm_token',
        'ip_address',
        'last_active_at',
    ];

    protected $casts = [
        'last_active_at' => 'datetime',
    ];

    /**
     * Get the user that owns the device
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Update last active timestamp
     */
    public function touch($attribute = null): bool
    {
        if ($attribute) {
            return parent::touch($attribute);
        }
        $this->last_active_at = now();

        return $this->save();
    }
}
