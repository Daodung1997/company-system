<?php

namespace App\Models;

use App\Constants\Master\Models\Job\JobStatusConst;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends BaseModel
{
    use HasFactory, SoftDeletes;

    public const TABLE_NAME = 't_jobs';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'code',
        'customer_id',
        'service_id',
        'worker_id',
        'description',
        'area_id',
        'address',
        'latitude',
        'longitude',
        'scheduled_date',
        'time_slot',
        'work_time_type',
        'work_start_time',
        'work_end_time',
        'user_address_id',
        'status',
        'quotation_price',
        'platform_fee',
        'total_amount',
        'discount_id',
        'discount_code',
        'discount_amount',
        'original_amount',
        'final_amount',
        'cancelled_reason',
        'started_at',
        'completed_at',
        'confirmed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'quotation_price' => 'decimal:0',
        'platform_fee' => 'decimal:0',
        'total_amount' => 'decimal:0',
        'discount_id' => 'integer',
        'discount_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = 'JOB'.date('Ymd').rand(1000, 9999);
            }
            if (empty($model->status)) {
                $model->status = JobStatusConst::WAITING_FOR_QUOTATION;
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function media()
    {
        return $this->hasMany(JobMedia::class, 'job_id');
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class, 'job_id');
    }

    public function invitedWorkers()
    {
        return $this->belongsToMany(User::class, 't_job_invited_workers', 'job_id', 'worker_id')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function quotation()
    {
        return $this->hasOne(Quotation::class, 'job_id')->where('status', 'accepted');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'job_id');
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class, 'job_id');
    }

    public function notes()
    {
        return $this->hasMany(JobNote::class, 'job_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'job_id');
    }

    public function userAddress()
    {
        return $this->belongsTo(UserAddress::class, 'user_address_id');
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }
}
