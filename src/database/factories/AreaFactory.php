<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Area>
 */
class AreaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => substr($this->faker->unique()->slug, 0, 15).rand(1000, 9999),
            'name' => $this->faker->city,
            'level' => 1,
            'status' => 'active',
        ];
    }
}
