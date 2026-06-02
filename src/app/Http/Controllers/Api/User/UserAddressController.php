<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Address\StoreAddressRequest;
use App\Http\Requests\User\Address\UpdateAddressRequest;
use App\Http\Resources\User\Address\UserAddressResource;
use App\Services\User\UserAddressService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\JsonResponse;

class UserAddressController extends Controller
{
    public function __construct(protected UserAddressService $addressService) {}

    public function index(): JsonResponse
    {
        $addresses = $this->addressService->list(auth()->id());

        return Response::success(UserAddressResource::collection($addresses)->resolve());
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $address = $this->addressService->create(auth()->id(), $request->validated());

        return Response::created((new UserAddressResource($address))->resolve());
    }

    public function update(UpdateAddressRequest $request, int $id): JsonResponse
    {
        $address = $this->addressService->update(auth()->id(), $id, $request->validated());

        return Response::success((new UserAddressResource($address))->resolve());
    }

    public function destroy(int $id): JsonResponse
    {
        $this->addressService->delete(auth()->id(), $id);

        return Response::success([]);
    }
}
