<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Quotation extends BaseMasterModel
{
    use HasFactory;

    public const TABLE_NAME = 't_quotations';

    public const PREFIX_CODE = 'Q';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'code',
        'job_id',
        'worker_id',
        'price',
        'platform_fee',
        'total_amount',
        'estimated_duration',
        'note',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price' => 'decimal:0',
        'platform_fee' => 'decimal:0',
        'total_amount' => 'decimal:0',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }
}
