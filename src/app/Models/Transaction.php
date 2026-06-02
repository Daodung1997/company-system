<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends BaseMasterModel
{
    use HasFactory, SoftDeletes;

    public const TABLE_NAME = 't_transactions';

    public const PREFIX_CODE = 'TXN';

    public const MAX_LENGTH_CODE = 20;

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'code',
        'type',
        'amount',
        'net_amount',
        'tax_amount',
        'tax_rate_type',
        'invoice_registration_number',
        'withholding_tax',
        'payment_method',
        'category',
        'transaction_date',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'withholding_tax' => 'decimal:2',
        'transaction_date' => 'date:Y-m-d',
    ];



    /**
     * Get the Documents associated with this transaction.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'transaction_id', 'id');
    }
}
