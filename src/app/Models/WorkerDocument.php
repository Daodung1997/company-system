<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkerDocument extends BaseModel
{
    use HasFactory;

    public const TABLE_NAME = 'm_worker_documents';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'worker_profile_id',
        'type',
        'file_code',
        'status',
        'rejection_reason', // if needed
        'verified_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function workerProfile()
    {
        return $this->belongsTo(WorkerProfile::class, 'worker_profile_id');
    }

    public function file()
    {
        return $this->belongsTo(Image::class, 'file_code', 'code');
    }
}
