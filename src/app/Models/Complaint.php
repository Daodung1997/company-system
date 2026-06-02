<?php

namespace App\Models;

class Complaint extends BaseMasterModel
{
    public const TABLE_NAME = 't_complaints';

    public const PREFIX_CODE = 'CP';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'code',
        'job_id',
        'description',
        'status',
        'resolution_note',
        'resolver_id',
        'created_by',
        'updated_by',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function evidence()
    {
        return $this->hasMany(ComplaintEvidence::class);
    }
}
