<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Withdrawal extends BaseMasterModel
{
    use HasFactory;

    public const TABLE_NAME = 't_withdrawals';

    public const PREFIX_CODE = 'WDR';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'code',
        'worker_id',
        'bank_account_id',
        'amount',
        'status',
        'processed_at',
        'processed_by',
        'failure_reason',
        'gateway_reference',
        'gateway_response',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:0',
        'processed_at' => 'datetime',
        'gateway_response' => 'array',
    ];

    // Relationships
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id', 'id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id', 'id');
    }

    public function processedBy()
    {
        return $this->belongsTo(\App\Models\Admin::class, 'processed_by', 'id');
    }

    public function logs()
    {
        return $this->hasMany(WithdrawalLog::class, 'withdrawal_id', 'id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereIn('status', [
            \App\Constants\Transaction\Models\Withdrawal\WithdrawalStatusConst::REQUESTED,
            \App\Constants\Transaction\Models\Withdrawal\WithdrawalStatusConst::PROCESSING,
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', \App\Constants\Transaction\Models\Withdrawal\WithdrawalStatusConst::COMPLETED);
    }
}
