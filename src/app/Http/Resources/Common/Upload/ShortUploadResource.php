<?php

namespace App\Http\Resources\Common\Upload;

use App\Constants\Commons\Disk;
use App\Constants\Commons\UploadResourceConst;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ShortUploadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $resource = [];
        // current field
        foreach (UploadResourceConst::getValues() as $value) {
            if (in_array($value, [UploadResourceConst::PATH_IMAGE_RESIZE, UploadResourceConst::PATH_IMAGE_ORIGINAL])) {
                $resource[$value] = Storage::disk($this->disk ?? Disk::IMAGE)->url($this->{$value});
            } else {
                $resource[$value] = $this->{$value};
            }
        }

        return $resource;
    }
}
