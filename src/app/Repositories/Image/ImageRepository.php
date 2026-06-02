<?php

namespace App\Repositories\Image;

use App\Models\Image;
use App\Repositories\Repository;

class ImageRepository extends Repository
{
    public function __construct(Image $model)
    {
        parent::__construct($model);
    }
}
