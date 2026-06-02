<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobMedia extends BaseModel
{
    use HasFactory;

    protected $table = 't_job_media';

    protected $fillable = [
        'job_id',
        'url',
        'type', // image/video
        'created_by',
        'updated_by',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }
}
