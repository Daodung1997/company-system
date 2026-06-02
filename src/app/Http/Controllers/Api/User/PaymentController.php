<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Payment\ConfirmPaymentRequest;
use App\Http\Requests\User\Payment\CreateGatewayPaymentRequest;
use App\Http\Requests\User\Payment\PayCashRequest;
use App\Http\Requests\User\Payment\StorePaymentRequest;
use App\Http\Resources\User\Payment\PaymentMethodResource;
use App\Http\Resources\User\Payment\PaymentResource;
use App\Services\User\PaymentService;
use App\Supports\Facades\Response\Response;
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
     * Get Payment Info
     * GET /api/user/customer/jobs/{id}/payment
     *
     * @param  int|string  $jobId
     */
    public function getPaymentInfo($jobId, Request $request): JsonResponse
    {
        $user = $request->user();
        $info = $this->paymentService->getPaymentInfo($jobId, $user);

        // Transform payment methods
        $info['payment_methods'] = PaymentMethodResource::collection($info['payment_methods'])->toArray($request);

        return Response::success($info);
    }

    /**
     * Create Payment
     * POST /api/user/customer/jobs/{id}/payment
     *
     * @param  int|string  $jobId
     */
    public function createPayment($jobId, StorePaymentRequest $request): JsonResponse
    {
        $user = $request->user();
        $payment = $this->paymentService->createPayment(
            $jobId,
            $request->input('payment_method'),
            $user
        );

        return Response::success((new PaymentResource($payment))->toArray($request));
    }

    /**
     * Create Gateway Payment
     * POST /api/user/customer/jobs/{id}/payment/gateway
     *
     * @param  int|string  $jobId
     */
    public function createGatewayPayment($jobId, CreateGatewayPaymentRequest $request): JsonResponse
    {
        $user = $request->user();
        $payment = $this->paymentService->createGatewayPayment(
            $jobId,
            $request->input('payment_method'),
            $user
        );

        $resource = new PaymentResource($payment);
        // Append pay_url if exists
        $data = $resource->toArray($request);
        if (isset($payment->pay_url)) {
            $data['pay_url'] = $payment->pay_url;
        }

        return Response::success($data);
    }

    /**
     * Confirm Payment
     * POST /api/user/customer/jobs/{id}/payment/confirm
     *
     * @param  int|string  $jobId
     */
    public function confirmPayment($jobId, ConfirmPaymentRequest $request): JsonResponse
    {
        $user = $request->user();
        $payment = $this->paymentService->confirmPayment(
            $jobId,
            $request->validated(),
            $user
        );

        return Response::success((new PaymentResource($payment))->toArray($request), 'Payment confirmation received');
    }

    /**
     * List payment methods
     * GET /api/user/payment-methods
     */
    public function listPaymentMethods(Request $request): JsonResponse
    {
        $methods = $this->paymentService->listPaymentMethods();

        return Response::success(PaymentMethodResource::collection($methods)->toArray($request));
    }

    /**
     * Pay Cash
     * POST /api/user/customer/jobs/{id}/payment/cash
     *
     * @param  int|string  $jobId
     */
    public function payCash($jobId, PayCashRequest $request): JsonResponse
    {
        $user = $request->user();
        $payment = $this->paymentService->processCashPayment($jobId, $user);

        return Response::success((new PaymentResource($payment))->toArray($request));
    }
}
