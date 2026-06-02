<?php

namespace App\Models;

class PaymentMethod extends BaseMasterModel
{
    public const TABLE_NAME = 'm_payment_methods';

    public const PREFIX_CODE = 'P';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'code',
        'name',
        'type',
        'config',
        'icon_url',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'config' => 'array',
    ];
}
