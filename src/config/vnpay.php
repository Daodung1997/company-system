<?php

return [
    'tmn_code' => env('VNPAY_TMN_CODE', ''),
    'hash_secret' => env('VNPAY_HASH_SECRET', ''),
    'url' => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
    'return_url' => env('VNPAY_RETURN_URL', 'http://localhost/api/payment/vnpay/return'),
    'version' => '2.1.0',
    'command' => 'pay',
    'curr_code' => 'VND',
    'locale' => 'vn',
    'order_type' => 'other',
];
