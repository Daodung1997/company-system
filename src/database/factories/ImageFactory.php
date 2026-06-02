<?php

namespace Database\Factories;

use App\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImageFactory extends Factory
{
    protected $model = Image::class;

    public function definition()
    {
        return [
            'code' => 'IMG'.$this->faker->unique()->numberBetween(1000, 9999),
            'origin_name' => $this->faker->word.'.jpg',
            'path_image_original' => 'images/'.$this->faker->uuid.'.jpg',
            'disk' => 'public',
            'extension' => 'jpg',
            'filesize' => 1024,
            'status' => 'draft',
        ];
    }
}
