<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkingHourConfig extends BaseModel
{
    use HasFactory;

    public const TABLE_NAME = 'working_hour_configs';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'is_default',
        'allow_overtime',
        'max_overtime_hours',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'allow_overtime' => 'boolean',
            'max_overtime_hours' => 'double',
        ];
    }
}
