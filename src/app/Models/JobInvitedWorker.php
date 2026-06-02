<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobInvitedWorker extends BaseModel
{
    use HasFactory;

    public const TABLE_NAME = 't_job_invited_workers';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'job_id',
        'worker_id',
        'status',
        'created_at',
        'updated_at',
    ];

    // Status constants
    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_REJECTED = 'rejected';

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }
}
