<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Discount\ListDiscountRequest;
use App\Http\Requests\Admin\Discount\StoreDiscountRequest;
use App\Http\Requests\Admin\Discount\UpdateDiscountRequest;
use App\Http\Resources\Admin\Discount\DiscountResource;
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
     * Display a listing of the resource with filters, sorts, search.
     */
    public function index(ListDiscountRequest $request)
    {
        $limit = $request->input('limit', 15);
        $filters = $request->input('filters', []);
        $sorts = $request->input('sorts', []);
        $search = $request->input('search', []);

        $discounts = $this->service->listAdmin($filters, $sorts, $search, $limit);

        return Response::pagination(
            DiscountResource::collection($discounts),
            $discounts->total(),
            $discounts->currentPage(),
            $discounts->perPage()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDiscountRequest $request)
    {
        $discount = $this->service->createAdmin($request->validated());

        return Response::created((new DiscountResource($discount))->resolve());
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $discount = $this->service->showAdmin($id);
        $discount->load(['jobs']); // Load voucher usage history (Jobs)

        return Response::success((new DiscountResource($discount))->resolve());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDiscountRequest $request, $id)
    {
        $discount = $this->service->updateAdmin($id, $request->validated());

        return Response::success((new DiscountResource($discount))->resolve());
    }

    /**
     * Toggle the status of a discount resource.
     */
    public function toggleStatus($id)
    {
        $discount = $this->service->toggleStatusAdmin($id);

        return Response::success([
            'message' => 'Discount status toggled successfully',
            'status' => $discount->status,
        ]);
    }
}
