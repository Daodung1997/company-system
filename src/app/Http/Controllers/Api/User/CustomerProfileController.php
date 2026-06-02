<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\CustomerProfile\ChangePasswordRequest;
use App\Http\Requests\User\CustomerProfile\UpdateProfileRequest;
use App\Http\Resources\User\CustomerProfileResource;
use App\Services\User\CustomerProfileService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\Request;

class CustomerProfileController extends Controller
{
    protected $service;

    public function __construct(CustomerProfileService $service)
    {
        $this->service = $service;
    }

    public function show(Request $request)
    {
        $data = $this->service->getProfile();

        return Response::success((new CustomerProfileResource($data))->resolve());
    }

    public function update(UpdateProfileRequest $request)
    {
        $data = $this->service->updateProfile($request->validated());

        return Response::success((new CustomerProfileResource($data))->resolve());
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $this->service->changePassword($request->validated());

        return Response::success(['message' => 'Password changed successfully']);
    }
}
