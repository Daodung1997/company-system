<?php

namespace App\Http\Resources\Common\Upload;

use App\Constants\Commons\Disk;
use App\Constants\Commons\Resource\CommonResourceConst;
use App\Constants\Commons\UploadResourceConst;
use App\Http\Resources\User\ShortUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UploadResource extends JsonResource
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

        $resource[CommonResourceConst::CREATED_AT] = $this->{CommonResourceConst::CREATED_AT};
        $resource[CommonResourceConst::UPDATED_AT] = $this->{CommonResourceConst::UPDATED_AT};
        $resource[CommonResourceConst::CREATED_BY] = new ShortUserResource($this->{CommonResourceConst::CREATED_BY});
        $resource[CommonResourceConst::UPDATED_BY] = new ShortUserResource($this->{CommonResourceConst::UPDATED_BY});

        return $resource;
    }
}
