<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankAccount extends BaseModel
{
    use HasFactory;

    public const TABLE_NAME = 'm_bank_accounts';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'user_id',
        'bank_name',
        'account_number',
        'account_name',
        'branch',
        'is_default',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // Scopes
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
