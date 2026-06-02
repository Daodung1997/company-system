<?php

namespace Database\Factories;

use App\Constants\Master\Models\ServiceCategory\ServiceCategoryLevelConst;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceCategory>
 */
class ServiceCategoryFactory extends Factory
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
            'name' => $this->faker->unique()->jobTitle,
            'status' => 'active',
            'level' => ServiceCategoryLevelConst::MAIN,
            'parent_id' => null,
        ];
    }

    /**
     * State for sub category.
     */
    public function sub(int $parentId): static
    {
        return $this->state(fn () => [
            'parent_id' => $parentId,
            'level' => ServiceCategoryLevelConst::SUB,
        ]);
    }
}
