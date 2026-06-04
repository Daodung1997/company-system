<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends Model
{
    use HasFactory;

    protected $table = 'company_settings';

    protected $fillable = [
        'company_id',
        'company_name',
        'company_name_kana',
        'tax_code',
        'corporate_number',
        'address_registered',
        'legal_representative',
        'representative_title',
        'representative_id_number',
        'representative_id_date',
        'representative_id_place',
        'charter_capital',
        'phone_number',
        'email',
        'fax',
        'postcode',
        'address',
        'website',
        'hanko_seal_path',
    ];

    protected $casts = [
        'representative_id_date' => 'date:Y-m-d',
    ];

    public function getPrimaryKey(): string
    {
        return $this->getKeyName();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
