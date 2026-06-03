<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeWorkHistory extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'employee_work_histories';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'employee_id',
        'department_id',
        'job_title_id',
        'start_date',
        'end_date',
        'salary',
        'note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'salary' => 'decimal:2',
    ];

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class, 'job_title_id', 'id');
    }
}
