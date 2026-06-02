<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends BaseModel
{
    use HasFactory;

    public const TABLE_NAME = 't_reviews';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'job_id',
        'reviewer_id',
        'target_id',
        'rating',
        'comment',
        'created_by',
        'updated_by',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function target()
    {
        return $this->belongsTo(User::class, 'target_id');
    }
}
