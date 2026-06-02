<?php

namespace App\Http\Controllers\Common;

use App\Constants\Commons\CommonConst;
use App\Http\Controllers\Controller;
use App\Http\Resources\Common\UserDeviceResource;
use App\Models\UserDevice;
use App\Supports\Facades\Response\Response;
use Exception;
use Illuminate\Http\JsonResponse;

class DeviceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * List all devices for current user
     */
    public function index(): JsonResponse
    {
        $devices = UserDevice::where('user_id', auth()->id())
            ->orderBy('last_active_at', 'desc')
            ->get();

        return Response::success([
            'devices' => UserDeviceResource::collection($devices),
        ]);
    }

    /**
     * Revoke (logout) a specific device
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $device = UserDevice::where('user_id', auth()->id())
                ->where('id', $id)
                ->first();

            if (! $device) {
                return Response::failure([CommonConst::MESSAGE => 'device.not_found'], 404);
            }

            $device->delete();

            return Response::success([CommonConst::MESSAGE => 'device.revoked']);
        } catch (Exception $e) {
            return Response::failure([CommonConst::MESSAGE => $e->getMessage()], 400);
        }
    }
}
