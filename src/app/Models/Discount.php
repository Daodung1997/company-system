<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends BaseMasterModel
{
    use HasFactory, SoftDeletes;

    public const TABLE_NAME = 'm_discounts';

    public const PREFIX_CODE = 'DIS';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'code',
        'title',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_order_amount',
        'total_quantity',
        'used_quantity',
        'max_uses_per_user',
        'start_date',
        'end_date',
        'status',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'total_quantity' => 'integer',
        'used_quantity' => 'integer',
        'max_uses_per_user' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'status' => 'integer',
    ];

    public function jobs()
    {
        return $this->hasMany(Job::class, 'discount_id');
    }
}
