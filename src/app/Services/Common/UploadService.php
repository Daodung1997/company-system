<?php

namespace App\Services\Common;

use App\Constants\Commons\CommonResizeImageConst;
use App\Constants\Commons\CommonTable;
use App\Constants\Commons\Disk;
use App\Constants\Commons\File;
use App\Constants\Commons\Resource\CommonResourceConst;
use App\Jobs\ResizeImageJob;
use App\Models\Image;
use App\Repositories\Repository;
use App\Services\AbstractService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class UploadService extends AbstractService
{
    protected Repository $imageRepo;

    public function __construct(Image $image)
    {
        $this->imageRepo = new Repository($image);
    }

    public function uploadMultiImage($request, string $paramName = File::PARAM_MULTI_IMAGE, string $disk = Disk::IMAGE, string $prefix = ''): array
    {
        $listData = [];
        try {
            if ($request->hasFile($paramName)) {
                foreach ($request->file($paramName) as $image) {
                    $newImage = $this->saveImageResize($image, $disk, $prefix);
                    $listData[] = $newImage;
                }
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        return $listData;
    }

    public function uploadMultiFile($request, string $paramName = File::PARAM_FILE, string $disk = Disk::FILE, string $prefix = ''): array
    {
        $listData = [];
        try {
            if ($request->hasFile($paramName)) {
                foreach ($request->file($paramName) as $image) {
                    $newImage = $this->saveImage($image, $disk, $prefix);
                    $listData[] = $newImage;
                }
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        return $listData;
    }

    public function uploadSingleImage($request, string $disk = Disk::IMAGE, string $prefix = '')
    {
        try {
            if ($request->hasFile(File::PARAM_SINGLE_IMAGE)) {
                return $this->saveImageResize($request->file(File::PARAM_SINGLE_IMAGE), $disk, $prefix);
            }

            return null;
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());

            return null;
        }
    }

    private function getTempName($originName, $extension): string
    {
        return Carbon::now()->timestamp.'_'.Str::random(10).'_'.$originName;
    }

    public function getImage($id)
    {
        return $this->imageRepo->findWhere(['id' => $id])->first();
    }

    public function saveImage($image, $disk, $prefix)
    {
        $originName = str_replace('#', '', $image->getClientOriginalName());

        $extension = $image->extension();
        $filename = $prefix ? $prefix.'/'.$this->getTempName($originName, $extension) :
            $this->getTempName($originName, $extension);
        $sizeFile = $image->getSize();

        $uploadStatus = Storage::disk($disk)->put($filename, file_get_contents($image));
        if (! $uploadStatus) {
            return null;
        }

        $dataImage = [
            'origin_name' => $originName,
            'path_image_original' => $filename,
            'disk' => $disk,
            'extension' => $extension,
            'filesize' => $sizeFile,
            'status' => File::STATUS_DRAFT,
            CommonTable::CREATED_BY => auth()->user()?->code ?? null,
        ];

        return $this->imageRepo->create($dataImage);
    }

    public function saveImageResize($image, $disk, $prefix)
    {
        $originName = str_replace('#', '', $image->getClientOriginalName());
        $extension = $image->extension();
        $filenameOnly = $this->getTempName($originName, $extension);

        $originalPath = 'originals/'.($prefix ? $prefix.'/' : '').$filenameOnly;
        Storage::disk($disk)->put($originalPath, file_get_contents($image));

        $resizePath = null;
        $sizeFile = 0;

        $dataImage = [
            'origin_name' => $originName,
            'path_image_resize' => $resizePath,
            'path_image_original' => $originalPath,
            'disk' => $disk,
            'extension' => $extension,
            'filesize' => $sizeFile,
            'status' => File::STATUS_DRAFT,
            CommonTable::CREATED_BY => auth()->user()?->code ?? null,
        ];

        $savedImage = $this->imageRepo->create($dataImage);

        dispatch(new ResizeImageJob($savedImage->id))->onQueue(CommonResizeImageConst::IMAGE_RESIZE);

        return $savedImage;
    }

    public function deleteDraftImage($inDays = 7)
    {
        $listImage = $this->imageRepo->getInstance()->where('status', File::STATUS_DRAFT)
            ->where(CommonResourceConst::CREATED_AT, '<', now()->subDays($inDays)->toDateString())
            ->get();

        foreach ($listImage as $image) {
            try {
                Storage::disk($image->disk)->delete($image->path);
                $image->delete();
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        }
    }

    public function optimizeQualityImage(int $imageId)
    {
        $imageEntity = $this->getImage($imageId);
        $disk = $imageEntity->disk ?? 'image';

        $manager = new ImageManager(new Driver);
        $imageTemp = $manager->read(Storage::disk($disk)->get($imageEntity->path_image_original));

        $imageTemp->scaleDown(width: 500);
        $imageName = $imageEntity->origin_name;
        $tempPath = $this->getTempImage($imageName);

        $imageTemp->save($tempPath);
        try {
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());

            return null;
        }

        return $tempPath;
    }

    public function getTempImage(string $imageName = '')
    {
        return public_path('images/temp_'.$imageName);
    }

    public function deleteListTempImage(array $listPathImage = [])
    {
        foreach ($listPathImage as $imagePath) {
            $this->deleteTempImage($imagePath);
        }
    }

    public function deleteTempImage(string $imagePath = '')
    {
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    public function setInUseImage(?array $listImage): bool
    {
        if (empty($listImage) || count($listImage) === 0) {
            return false;
        }

        return $this->imageRepo->findWhereIn('id', $listImage)
            ->modelUpdate(['status' => File::STATUS_IN_USE]);
    }

    public function setDraftImage(?array $listImage): bool
    {
        if (empty($listImage) || count($listImage) === 0) {
            return false;
        }

        return $this->imageRepo->findWhereIn('id', $listImage)
            ->modelUpdate(['status' => File::STATUS_DRAFT]);
    }
}
