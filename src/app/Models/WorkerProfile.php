<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkerProfile extends BaseModel
{
    use HasFactory;

    public const TABLE_NAME = 'm_worker_profiles';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'user_id',
        'phone',
        'dob',
        'id_card_number',
        'id_card_issue_date',
        'permanent_address',
        'selfie_id',
        'id_card_front_id',
        'id_card_back_id',
        'gender',
        'address',
        'avatar_code',
        'experience_years',
        'skill_description',
        'profile_status',
        'activity_status',
        'availability',
        'rejection_reason',
        'suspend_reason',
        'approved_at',
        'avg_rating',
        'total_completed_jobs',
        'total_cancelled_jobs',
        'latitude',
        'longitude',
        'certificates',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'availability' => 'boolean',
        'approved_at' => 'datetime',
        'dob' => 'date',
        'id_card_issue_date' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'certificates' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function avatar()
    {
        return $this->belongsTo(Image::class, 'avatar_code', 'code');
    }

    public function selfie()
    {
        return $this->belongsTo(Image::class, 'selfie_id');
    }

    public function idCardFront()
    {
        return $this->belongsTo(Image::class, 'id_card_front_id');
    }

    public function idCardBack()
    {
        return $this->belongsTo(Image::class, 'id_card_back_id');
    }

    public function services()
    {
        return $this->hasMany(WorkerService::class, 'worker_profile_id');
    }

    public function areas()
    {
        return $this->hasMany(WorkerArea::class, 'worker_profile_id');
    }

    public function documents()
    {
        return $this->hasMany(WorkerDocument::class, 'worker_profile_id');
    }

    public function timeSlots()
    {
        return $this->hasMany(WorkerTimeSlot::class, 'worker_profile_id');
    }

    public function registrationHistories()
    {
        return $this->hasMany(WorkerRegistrationHistory::class, 'worker_profile_id');
    }
}
