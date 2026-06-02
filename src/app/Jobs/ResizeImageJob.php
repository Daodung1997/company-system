<?php

namespace App\Jobs;

use App\Models\Image;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ResizeImageJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    protected $imageId;

    public function __construct($imageId)
    {
        $this->imageId = $imageId;
    }

    public function handle()
    {
        $image = Image::find($this->imageId);

        if (! $image) {
            Log::error("Image not found: ID {$this->imageId}");

            return;
        }

        $disk = $image->disk ?? 'public';
        $originalPath = $image->path_image_original;

        if (! Storage::disk($disk)->exists($originalPath)) {
            Log::error("Original image does not exist: {$originalPath}");

            return;
        }

        try {
            $manager = new ImageManager(new Driver);
            $img = $manager->read(Storage::disk($disk)->get($originalPath));

            // Resize to max width 800, constrains aspect ratio
            $img->scale(width: 800);

            $filename = basename($originalPath);
            $prefixPath = str_replace('originals/', '', dirname($originalPath));
            $resizePath = 'image_resize/'.($prefixPath !== '.' ? $prefixPath.'/' : '').$filename;

            // Save resized image with 80% quality
            Storage::disk($disk)->put($resizePath, (string) $img->toJpeg(quality: 80));

            $image->update([
                'path_image_resize' => $resizePath,
                'filesize' => Storage::disk($disk)->size($resizePath),
            ]);

        } catch (\Exception $e) {
            Log::error("Resize failed for image {$this->imageId}: ".$e->getMessage());
        }
    }
}
