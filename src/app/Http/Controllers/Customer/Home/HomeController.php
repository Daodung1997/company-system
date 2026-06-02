<?php

namespace App\Http\Controllers\Customer\Home;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\Home\CustomerHomeResource;
use App\Services\Customer\Home\HomeService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    public function __construct(
        protected HomeService $homeService
    ) {}

    public function getHome(): JsonResponse
    {
        $data = $this->homeService->getHome(auth()->id());

        return Response::success((new CustomerHomeResource((object) $data))->resolve());
    }
}
