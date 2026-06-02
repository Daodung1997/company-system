<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends BaseModel
{
    use HasFactory;

    public const TABLE_NAME = 't_payments';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'code',
        'job_id',
        'amount',
        'platform_fee',
        'worker_earning',
        'payment_method',
        'gateway_provider',
        'gateway_order_id',
        'gateway_request_data',
        'status',
        'transaction_reference',
        'paid_at',
        'refunded_at',
        'refunded_amount',
        'description',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = 'PAY'.date('Ymd').rand(1000, 9999);
            }
        });
    }

    protected $casts = [
        'amount' => 'decimal:0',
        'platform_fee' => 'decimal:0',
        'worker_earning' => 'decimal:0',
        'refunded_amount' => 'decimal:0',
        'gateway_request_data' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function paymentMethodDetail()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method', 'code');
    }
}
