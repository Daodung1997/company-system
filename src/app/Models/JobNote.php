<?php

namespace App\Models;

class JobNote extends BaseModel
{
    public const TABLE_NAME = 't_job_notes';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'job_id',
        'admin_id',
        'note',
        'created_by',
        'updated_by',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
