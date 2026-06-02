<?php

namespace Database\Factories;

use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Discount>
 */
class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'VOUCHER'.$this->faker->unique()->numberBetween(1000, 9999),
            'title' => $this->faker->sentence(3),
            'discount_type' => 'PERCENTAGE',
            'discount_value' => 10.00,
            'max_discount_amount' => 50000.00,
            'min_order_amount' => 100000.00,
            'total_quantity' => 100,
            'used_quantity' => 0,
            'max_uses_per_user' => 1,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(10),
            'status' => 1,
            'note' => $this->faker->paragraph,
        ];
    }
}
