<?php

namespace App\Http\Controllers\Worker\Home;

use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\Home\ToggleOnlineStatusRequest;
use App\Http\Resources\Worker\Home\WorkerHomeResource;
use App\Services\Worker\Home\HomeService;
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

        return Response::success((new WorkerHomeResource((object) $data))->resolve());
    }

    public function toggleStatus(ToggleOnlineStatusRequest $request): JsonResponse
    {
        $this->homeService->toggleStatus(auth()->id(), $request->input('is_online'));

        return Response::success([
            'is_online' => $request->input('is_online'),
            'message' => 'Status updated successfully',
        ]);
    }
}
