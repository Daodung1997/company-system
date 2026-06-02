<?php

namespace App\Models;

class WorkerService extends BaseModel
{
    public const TABLE_NAME = 'm_worker_services';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'worker_profile_id',
        'service_category_id',
        'created_by',
        'updated_by',
    ];

    public function workerProfile()
    {
        return $this->belongsTo(WorkerProfile::class, 'worker_profile_id');
    }

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }
}
