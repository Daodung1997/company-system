<?php

namespace App\Http\Controllers\Api\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Finance\RefundRequest;
use App\Http\Resources\Admin\Finance\PaymentResource;
use App\Services\Admin\Finance\PaymentService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function index(Request $request)
    {
        $payments = $this->paymentService->list($request);

        return Response::pagination(
            PaymentResource::collection($payments),
            $payments->total(),
            $payments->currentPage(),
            $payments->perPage()
        );
    }

    public function show($id)
    {
        $payment = $this->paymentService->show($id);

        return Response::success((new PaymentResource($payment))->resolve());
    }

    public function refund(RefundRequest $request, $id)
    {
        $payment = $this->paymentService->refund($id, $request->validated());

        return Response::success((new PaymentResource($payment))->resolve(), 'Payment refund processed successfully');
    }
}
