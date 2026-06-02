<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends BaseMasterModel
{
    use HasFactory;

    public const PREFIX_CODE = 'DEP';

    public const TABLE_NAME = 'departments';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public function jobTitles()
    {
        return $this->hasMany(JobTitle::class, 'department_id', 'id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'department_id', 'id');
    }
}
