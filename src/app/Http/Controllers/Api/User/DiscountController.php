<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Discount\CheckDiscountRequest;
use App\Http\Resources\User\Discount\DiscountCheckResource;
use App\Services\User\DiscountService;
use App\Supports\Facades\Response\Response;

class DiscountController extends Controller
{
    protected $service;

    public function __construct(DiscountService $service)
    {
        $this->service = $service;
    }

    /**
     * Check voucher validity for Customer.
     */
    public function check(CheckDiscountRequest $request)
    {
        $code = $request->input('code');
        $price = $request->input('price');
        $userId = auth()->id();

        // Validate voucher against 5 rules.
        // It throws BusinessException with detailed messages if invalid.
        $discount = $this->service->validateVoucher($code, $price, $userId);

        // If price is supplied, calculate potential discount amount and final order price.
        if ($price !== null) {
            $discountAmount = $this->service->calculateDiscount($discount, $price);
            $discount->discount_amount = $discountAmount;
            $discount->final_amount = $price - $discountAmount;
        }

        $discount->is_valid = true;

        return Response::success((new DiscountCheckResource($discount))->resolve());
    }
}
