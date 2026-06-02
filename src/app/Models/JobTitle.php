<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobTitle extends BaseMasterModel
{
    use HasFactory;

    public const PREFIX_CODE = 'JOB';

    public const TABLE_NAME = 'job_titles';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'code',
        'department_id',
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'job_title_id', 'id');
    }
}
