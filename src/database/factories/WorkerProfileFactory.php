<?php

namespace Database\Factories;

use App\Constants\Master\Models\WorkerProfile\WorkerProfileStatus;
use App\Models\WorkerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkerProfileFactory extends Factory
{
    protected $model = WorkerProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            // dump('Factory Definition Running'),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'avatar_code' => null,
            'experience_years' => $this->faker->numberBetween(1, 10),
            'skill_description' => $this->faker->paragraph(),
            'profile_status' => WorkerProfileStatus::PENDING,
            'activity_status' => 'inactive',
            'availability' => false,
            'rejection_reason' => null,
            'approved_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_status' => WorkerProfileStatus::APPROVED,
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_status' => WorkerProfileStatus::REJECTED,
            'rejection_reason' => 'Documents unclear',
        ]);
    }

    public function incomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_status' => WorkerProfileStatus::INCOMPLETE,
        ]);
    }
}
