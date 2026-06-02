<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Image extends BaseMasterModel
{
    use HasFactory, SoftDeletes;

    public const TABLE_NAME = 't_images';

    const PREFIX_CODE = 'IMG';

    const MAX_LENGTH_CODE = 20;

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'code',
        'origin_name',
        'path_image_original',
        'path_image_resize',
        'disk',
        'extension',
        'filesize',
        'status',
        'created_by',
        'updated_by',
    ];

    public function getUrlAttribute()
    {
        return Storage::disk($this->disk)->url($this->path_image_original);
    }

    public function url()
    {
        return $this->getUrlAttribute();
    }
}
