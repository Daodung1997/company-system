<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class WithdrawalLog extends BaseModel
{
    use HasFactory;

    public const TABLE_NAME = 't_withdrawal_logs';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'withdrawal_id',
        'event',
        'status',
        'payload',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function withdrawal()
    {
        return $this->belongsTo(Withdrawal::class, 'withdrawal_id', 'id');
    }
}
