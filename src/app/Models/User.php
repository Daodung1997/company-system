<?php

namespace App\Models;

use App\Constants\Master\Models\User\UserRoleConst;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends BaseAuthenticateModel implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    public const PREFIX_CODE = 'U';

    public const TABLE_NAME = 'm_users';

    protected $table = self::TABLE_NAME;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'email',
        'password',
        'role',
        'status',
        'block_reason',
        'email_verified_at',
        'last_login_at',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function customerProfile()
    {
        return $this->hasOne(CustomerProfile::class, 'user_id', 'id');
    }

    public function workerProfile()
    {
        return $this->hasOne(WorkerProfile::class, 'user_id', 'id');
    }

    public function workerJobs()
    {
        return $this->hasMany(Job::class, 'worker_id', 'id');
    }

    public function workerReviews()
    {
        return $this->hasMany(Review::class, 'target_id', 'id');
    }

    public function getRolesMetadata(): array
    {
        $roles = [];
        if ($this->customerProfile()->exists()) {
            $roles[] = strtoupper(UserRoleConst::CUSTOMER);
        }
        if ($this->workerProfile()->exists()) {
            $roles[] = strtoupper(UserRoleConst::WORKER);
        }

        return $roles;
    }

    public function checkIsProfileCompleted(): bool
    {
        $profile = $this->customerProfile;
        if (! $profile) {
            return false;
        }

        return ! empty($profile->phone);
    }

    public function getWorkerStatusMetadata(): ?string
    {
        $profile = $this->workerProfile;
        if (! $profile) {
            return null;
        }

        return strtoupper($profile->profile_status);
    }
}
