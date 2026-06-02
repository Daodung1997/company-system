<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Area extends BaseMasterModel
{
    use HasFactory;

    public const TABLE_NAME = 'm_areas';

    public const PREFIX_CODE = 'R';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'code',
        'name',
        'parent_id',
        'level',
        'status',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    public function parent()
    {
        return $this->belongsTo(Area::class, 'parent_id');
    }
}
