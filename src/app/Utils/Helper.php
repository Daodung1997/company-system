<?php

namespace App\Utils;

use App\Constants\Commons\App;
use App\Constants\Commons\CommonHelperConst;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Helper
{
    public static function getCurrentAuthGuard(): ?string
    {
        $guards = ['api', 'customer'];

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return $guard;
            }
        }

        return null;
    }

    public static function setInputEncoding($storageFile)
    {
        $fileContent = file_get_contents($storageFile);
        self::detectAndSetEncoding($fileContent);
    }

    public static function setInputEncodingContentFile($fileContent)
    {
        self::detectAndSetEncoding($fileContent);
    }

    private static function detectAndSetEncoding(&$fileContent)
    {
        $enc = mb_detect_encoding($fileContent, [
            CommonHelperConst::SJIS,
            CommonHelperConst::SJIS_WIN,
            CommonHelperConst::SJIS_MAC,
            CommonHelperConst::UTF_8,
            CommonHelperConst::ISO_8859_1,
        ], true);

        if (in_array($enc, [
            CommonHelperConst::SJIS_WIN,
            CommonHelperConst::SJIS_MAC,
        ])) {
            $enc = CommonHelperConst::SJIS;
        } elseif ($enc === CommonHelperConst::ISO_8859_1) {
            $enc = CommonHelperConst::UTF_16LE;
        }

        if ($enc !== CommonHelperConst::UTF_8) {
            $fileContent = mb_convert_encoding($fileContent, CommonHelperConst::UTF_8, $enc);
        }

        config()->set(CommonHelperConst::EXCEL_IMPORTS_CSV_INPUT_ENCODING, $enc);
    }

    public static function convertTimezone($datetime, $timezone = null)
    {
        if (empty($timezone)) {
            $timezone = auth()->user()->getTimezone();
        }

        return Carbon::parse($datetime)->setTimezone($timezone);
    }

    public static function getDateTimezone($datetime, $timezone = null)
    {
        if (empty($timezone)) {
            $timezone = auth()->user()->getTimezone();
        }

        return Carbon::parse($datetime)->setTimezone($timezone)->toDateString();
    }

    public static function getWithFormatTimezone($datetime, $format = App::FORMAT_HOUR, $timezone = null)
    {
        if (empty($timezone)) {
            $timezone = auth()->user()->getTimezone();
        }

        return Carbon::parse($datetime)->setTimezone($timezone)->format($format);
    }

    public static function getWithTimestamp($datetime, $format = App::FORMAT_HOUR, $timezone = null)
    {
        if (empty($timezone)) {
            $timezone = auth()->user()->getTimezone();
        }

        return Carbon::parse($datetime)->setTimezone($timezone)->timestamp;
    }

    public static function convertToUTC($datetime, $format = App::DATE_FORMAT_CALENDAR, $timezone = App::TIMEZONE_JAPAN)
    {
        return Carbon::createFromFormat($format, $datetime, $timezone)->setTimezone(App::TIMEZONE_UTC);
    }

    public static function br2n($text)
    {
        return str_replace('<br>', "\n", $text);
    }

    public static function checkExistsMatterAccess($startTime)
    {
        $start = Carbon::parse($startTime);
        $now = Carbon::now();

        return $now->gte($start->copy()->addMinutes(Matter::MATTER_ACCESS_EDIT_LIMIT));
    }

    public static function convertWeekDayString(int $weekDay)
    {
        return Carbon::getDays()[$weekDay];
    }

    public static function getFullnameUser($user)
    {
        if (empty($user) || empty($user->first_name) || empty($user->last_name)) {
            return '';
        }

        return $user->first_name.'　'.$user->last_name;
    }

    public static function getDayNameJapanese()
    {
        $weekdays = [
            '日',
            '月',
            '火',
            '水',
            '木',
            '金',
            '土',
        ];
        $weekday = Carbon::now()->dayOfWeek;

        return $weekdays[$weekday];
    }

    public static function getProductCodeFromPackageCode($packageCode)
    {
        $product = explode('-', $packageCode);

        return $product[0];
    }

    public static function endOfWeek(Carbon $carbon)
    {
        return $carbon->copy()->endOfWeek(Carbon::WEDNESDAY);
    }

    public static function startOfWeek(Carbon $carbon)
    {
        return $carbon->copy()->startOfWeek(Carbon::THURSDAY);
    }

    public static function getZipCode($staff)
    {
        if (empty($staff) || empty($staff->branch->zipcode)) {
            return '';
        }

        return $staff->branch->zipcode;
    }

    public static function getFullAddress($staff)
    {
        if (empty($staff) || empty($staff->branch->city) || empty($staff->branch->district) || empty($staff->branch->town) || empty($staff->branch->building_name)) {
            return '';
        }

        return $staff->branch->city.$staff->branch->district.$staff->branch->town.$staff->branch->building_name;
    }

    public static function getFAX($staff)
    {
        if (empty($staff) || empty($staff->branch->fax)) {
            return '';
        }

        return substr($staff->branch->fax, 0, 3).'-'.substr($staff->branch->fax, 3, 3).'-'.substr($staff->branch->fax, 6, 4);
    }

    public static function getTEL($staff)
    {
        if (empty($staff) || empty($staff->branch->phone_number)) {
            return '';
        }

        return substr($staff->branch->phone_number, 0, 3).'-'.substr($staff->branch->phone_number, 3, 3).'-'.substr($staff->branch->phone_number, 6, 4);
    }

    public static function formatNumber($number)
    {
        if (is_null($number)) {
            return null;
        }

        $formatted = rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');

        return $formatted;
    }

    public static function formatPhoneNumber($phone)
    {
        if (! $phone) {
            return '';
        }

        return strlen($phone) === 10
            ? substr($phone, 0, 3).'-'.substr($phone, 3, 3).'-'.substr($phone, 6, 4)
            : (strlen($phone) === 11
                ? substr($phone, 0, 3).'-'.substr($phone, 3, 4).'-'.substr($phone, 7, 4)
                : $phone);
    }
}
