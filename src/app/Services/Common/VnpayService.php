<?php

namespace App\Services\Common;

use App\Models\Payment;
use Illuminate\Support\Facades\Request;

class VnpayService
{
    /**
     * Build VNPay Payment URL (Alias for getPayUrl)
     *
     * @return string format pay_url
     */
    public function buildPayUrl(Payment $payment): string
    {
        return $this->getPayUrl($payment);
    }

    /**
     * Get VNPay Payment URL
     */
    public function getPayUrl(Payment $payment): string
    {
        $vnp_TmnCode = config('vnpay.tmn_code');
        $vnp_HashSecret = config('vnpay.hash_secret');
        $vnp_Url = config('vnpay.url');
        $vnp_Returnurl = config('vnpay.return_url');

        $vnp_TxnRef = $payment->gateway_order_id;
        // Access job safely
        $jobCode = $payment->job ? $payment->job->code : 'N/A';
        $vnp_OrderInfo = 'Thanh toan Job #'.$jobCode;
        $vnp_OrderType = config('vnpay.order_type', 'other');
        $vnp_Amount = floatval($payment->amount) * 100;
        $vnp_Locale = config('vnpay.locale', 'vn');
        $vnp_IpAddr = Request::ip();

        $inputData = [
            'vnp_Version' => config('vnpay.version', '2.1.0'),
            'vnp_TmnCode' => $vnp_TmnCode,
            'vnp_Amount' => $vnp_Amount,
            'vnp_Command' => config('vnpay.command', 'pay'),
            'vnp_CreateDate' => date('YmdHis'),
            'vnp_CurrCode' => config('vnpay.curr_code', 'VND'),
            'vnp_IpAddr' => $vnp_IpAddr,
            'vnp_Locale' => $vnp_Locale,
            'vnp_OrderInfo' => $vnp_OrderInfo,
            'vnp_OrderType' => $vnp_OrderType,
            'vnp_ReturnUrl' => $vnp_Returnurl,
            'vnp_TxnRef' => $vnp_TxnRef,
            // 15 minutes expire
            'vnp_ExpireDate' => date('YmdHis', strtotime('+15 minutes')),
        ];

        ksort($inputData);
        $query = '';
        $i = 0;
        $hashdata = '';
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&'.urlencode($key).'='.urlencode($value);
            } else {
                $hashdata .= urlencode($key).'='.urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key).'='.urlencode($value).'&';
        }

        $vnp_Url = $vnp_Url.'?'.$query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash='.$vnpSecureHash;
        }

        return $vnp_Url;
    }

    /**
     * Get Checkout URL (Alias for getPayUrl)
     */
    public function getCheckoutUrl(Payment $payment): string
    {
        return $this->getPayUrl($payment);
    }

    /**
     * Verify VNPay IPN checksum
     *
     * @param  array  $inputData  Data from query parameters
     */
    public function verifyIpnHash(array $inputData): bool
    {
        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
        $vnp_HashSecret = config('vnpay.hash_secret');

        $data = [];
        foreach ($inputData as $key => $value) {
            if (substr($key, 0, 4) == 'vnp_' && $key != 'vnp_SecureHash' && $key != 'vnp_SecureHashType') {
                $data[$key] = $value;
            }
        }
        ksort($data);

        $hashdata = '';
        $i = 0;
        foreach ($data as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&'.urlencode($key).'='.urlencode($value);
            } else {
                $hashdata .= urlencode($key).'='.urlencode($value);
                $i = 1;
            }
        }

        $computedHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);

        return hash_equals($computedHash, $vnp_SecureHash);
    }
}
