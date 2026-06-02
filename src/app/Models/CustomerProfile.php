<?php

namespace App\Models;

class CustomerProfile extends BaseModel
{
    public const TABLE_NAME = 'm_customer_profiles';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'user_id',
        'phone',
        'address',
        'area_id',
        'avatar_code',
        'gender',
        'birthday',
        'loyalty_points',
        'created_by',
        'updated_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function avatar()
    {
        return $this->belongsTo(Image::class, 'avatar_code', 'code');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    protected $casts = [
        'birthday' => 'date',
    ];
}
