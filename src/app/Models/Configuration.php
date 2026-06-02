<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Configuration extends BaseMasterModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'm_configurations';

    protected $fillable = [
        'key',
        'value',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'value' => 'string', // Value is always string, parsed by logic if needed
    ];
}
