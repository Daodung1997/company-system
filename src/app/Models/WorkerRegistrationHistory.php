<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkerRegistrationHistory extends BaseModel
{
    use HasFactory;

    public const TABLE_NAME = 't_worker_registration_histories';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'worker_profile_id',
        'attempt_number',
        'phone',
        'dob',
        'id_card_number',
        'id_card_issue_date',
        'permanent_address',
        'selfie_id',
        'id_card_front_id',
        'id_card_back_id',
        'gender',
        'experience_years',
        'skill_description',
        'service_ids',
        'area_ids',
        'action',
        'action_by',
        'action_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'dob' => 'date',
        'id_card_issue_date' => 'date',
        'service_ids' => 'array',
        'area_ids' => 'array',
    ];

    public function workerProfile()
    {
        return $this->belongsTo(WorkerProfile::class, 'worker_profile_id');
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

    public function actionByAdmin()
    {
        return $this->belongsTo(Admin::class, 'action_by');
    }
}
