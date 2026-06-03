<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeShift extends BaseModel
{
    use HasFactory;

    public const TABLE_NAME = 't_employee_shifts';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'employee_id',
        'working_hour_config_id',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function workingHourConfig()
    {
        return $this->belongsTo(WorkingHourConfig::class, 'working_hour_config_id', 'id');
    }
}
