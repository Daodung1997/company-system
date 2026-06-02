<?php

namespace App\Models;

use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionStatusConst;
use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionTypeConst;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WalletTransaction extends BaseMasterModel
{
    use HasFactory;

    public const TABLE_NAME = 't_wallet_transactions';

    public const PREFIX_CODE = 'WT';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'code',
        'worker_id',
        'job_id',
        'withdrawal_id',
        'type',
        'amount',
        'status',
        'release_at',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:0',
        'release_at' => 'datetime',
    ];

    // Relationships
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id', 'id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id', 'id');
    }

    public function withdrawal()
    {
        return $this->belongsTo(Withdrawal::class, 'withdrawal_id', 'id');
    }

    // Scopes
    public function scopeEarning($query)
    {
        return $query->where('type', WalletTransactionTypeConst::EARNING);
    }

    public function scopeWithdrawal($query)
    {
        return $query->where('type', WalletTransactionTypeConst::WITHDRAWAL);
    }

    public function scopePending($query)
    {
        return $query->where('status', WalletTransactionStatusConst::PENDING);
    }

    public function scopeReleased($query)
    {
        return $query->where('status', WalletTransactionStatusConst::RELEASED);
    }
}
