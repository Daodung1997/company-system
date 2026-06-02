<?php

namespace App\Models;

class WorkerArea extends BaseModel
{
    public const TABLE_NAME = 'm_worker_areas';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'worker_profile_id',
        'area_id',
        'created_by',
        'updated_by',
    ];

    public function workerProfile()
    {
        return $this->belongsTo(WorkerProfile::class, 'worker_profile_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }
}
