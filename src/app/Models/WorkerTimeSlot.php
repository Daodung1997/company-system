<?php

namespace App\Models;

class WorkerTimeSlot extends BaseModel
{
    public const TABLE_NAME = 'm_worker_time_slots';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'worker_profile_id',
        'time_slot',
    ];

    public function workerProfile()
    {
        return $this->belongsTo(WorkerProfile::class, 'worker_profile_id');
    }
}
