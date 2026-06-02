<?php

namespace App\Http\Controllers\Common;

use App\Constants\Commons\App;
use App\Constants\Commons\Disk;
use App\Constants\Commons\File;
use App\Http\Controllers\Controller;
use App\Http\Requests\Common\Image\UploadMultiImageRequest;
use App\Http\Requests\Common\Image\UploadSingleImageRequest;
use App\Http\Resources\Common\Upload\UploadResource;
use App\Services\Common\UploadService;
use App\Supports\Facades\Response\Response;
use Illuminate\Http\JsonResponse;

class UploadController extends Controller
{
    protected UploadService $uploadService;

    public function __construct(UploadService $uploadService)
    {
        ini_set('memory_limit', -1);
        $this->uploadService = $uploadService;
    }

    public function uploadImages(UploadMultiImageRequest $request): JsonResponse
    {
        $listImage = $this->uploadService->uploadMultiImage($request);

        if (empty($listImage)) {
            Response::failure([__('common.update.fail')]);
        }

        return Response::success([File::PARAM_MULTI_IMAGE => UploadResource::collection($listImage)]);
    }

    public function uploadSingleImage(UploadSingleImageRequest $request): JsonResponse
    {
        $image = $this->uploadService->uploadSingleImage($request);
        if (empty($image)) {
            Response::failure([__('common.update.fail')]);
        }

        return Response::success([File::PARAM_SINGLE_IMAGE => new UploadResource($image)]);
    }

    public function uploadSignature(UploadSingleImageRequest $request): JsonResponse
    {
        $image = $this->uploadService->uploadSingleImage($request, Disk::SIGNATURE, now()->format(App::DATE_FORMAT));
        if (empty($image)) {
            Response::failure([__('common.update.fail')]);
        }

        return Response::success([File::PARAM_SINGLE_IMAGE => new UploadResource($image)]);
    }
}
