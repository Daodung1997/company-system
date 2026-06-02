<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PlatformFee Model
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $description
 * @property string $fee_type
 * @property float $amount
 * @property string $start_date
 * @property string|null $end_date
 * @property string $status
 */
class PlatformFee extends BaseModel
{
    use SoftDeletes;

    protected $table = 'm_platform_fees';

    protected $fillable = [
        'code',
        'name',
        'description',
        'fee_type',
        'amount',
        'start_date',
        'end_date',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];
}
