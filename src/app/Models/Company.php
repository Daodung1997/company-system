<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends BaseMasterModel
{
    use HasFactory;

    public const TABLE_NAME = 'companies';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'code',
        'name',
        'name_kana',
        'tax_code',
        'corporate_number',
        'address_registered',
        'legal_representative',
        'hanko_seal_path',
        'fax',
        'phone_number',
        'postcode',
        'address',
        'email',
        'note',
        'status',
    ];
}
