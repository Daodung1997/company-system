<?php

namespace App\Http\Controllers\Api\Webhook;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhook\VnpayIpnRequest;
use App\Services\User\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * @var PaymentService
     */
    protected $paymentService;

    /**
     * PaymentController constructor.
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Handle VNPay IPN
     * GET /api/webhooks/payment/vnpay/ipn
     */
    public function vnpayIpn(VnpayIpnRequest $request): JsonResponse
    {
        $result = $this->paymentService->handleVnpayIpn($request->all());

        return response()->json($result);
    }

    /**
     * Handle VNPay Return
     * GET /api/payment/vnpay/return
     */
    public function vnpayReturn(Request $request): JsonResponse
    {
        $result = $this->paymentService->handleVnpayReturn($request->all());

        return response()->json($result);
    }
}
