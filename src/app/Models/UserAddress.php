<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAddress extends BaseModel
{
    use HasFactory, SoftDeletes;

    public const TABLE_NAME = 'm_user_addresses';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'user_id',
        'label',
        'receiver_name',
        'receiver_phone',
        'area_id',
        'ward_id',
        'address_detail',
        'latitude',
        'longitude',
        'is_default',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function ward()
    {
        return $this->belongsTo(Area::class, 'ward_id');
    }
}
