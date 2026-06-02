<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Timesheet extends BaseModel
{
    use HasFactory;

    public const TABLE_NAME = 'timesheets';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'timezone',
        'status',
        'note',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
