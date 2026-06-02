<?php

namespace App\Constants\Transaction\Models\Transaction;

use App\Traits\ConstTrait;

class TaxRateTypeConst
{
    use ConstTrait;

    const VAT_8_VN = 'VAT_8_VN';
    const VAT_10_VN = 'VAT_10_VN';
    const CT_8_JP = 'CT_8_JP';
    const CT_10_JP = 'CT_10_JP';
    const NONE = 'NONE';

    const LABELS = [
        self::VAT_8_VN => 'VAT 8% (VN)',
        self::VAT_10_VN => 'VAT 10% (VN)',
        self::CT_8_JP => 'Consumption Tax 8% (JP)',
        self::CT_10_JP => 'Consumption Tax 10% (JP)',
        self::NONE => 'Không thuế (0%)',
    ];

    /**
     * Get numeric tax rate value from the code name.
     */
    public static function getRate(string $type): float
    {
        return match ($type) {
            self::VAT_8_VN, self::CT_8_JP => 0.08,
            self::VAT_10_VN, self::CT_10_JP => 0.10,
            default => 0.00,
        };
    }
}
