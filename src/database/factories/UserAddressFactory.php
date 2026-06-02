<?php

namespace Database\Factories;

use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserAddress>
 */
class UserAddressFactory extends Factory
{
    protected $model = UserAddress::class;

    public function definition(): array
    {
        return [
            'label' => $this->faker->randomElement(['Nhà riêng', 'Văn phòng', 'Nhà bạn']),
            'receiver_name' => $this->faker->name,
            'receiver_phone' => $this->faker->phoneNumber,
            'address_detail' => $this->faker->streetAddress,
            'latitude' => $this->faker->latitude(8.0, 23.0),
            'longitude' => $this->faker->longitude(102.0, 110.0),
            'is_default' => false,
        ];
    }
}
