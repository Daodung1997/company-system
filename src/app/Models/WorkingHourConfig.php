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
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'is_default',
        'saturday_mode',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'is_default' => 'boolean',
            'saturday_mode' => 'integer',
        ];
    }
}
