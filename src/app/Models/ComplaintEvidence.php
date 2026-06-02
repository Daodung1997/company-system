<?php

namespace App\Models;

class ComplaintEvidence extends BaseModel
{
    public const TABLE_NAME = 't_complaint_evidence';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'complaint_id',
        'file_code',
        'type',
        'created_by',
        'updated_by',
    ];

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    public function file()
    {
        return $this->belongsTo(Image::class, 'file_code', 'code');
    }
}
