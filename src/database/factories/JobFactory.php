<?php

namespace Database\Factories;

use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\Job\JobTimeSlotConst;
use App\Models\Area;
use App\Models\Job;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobFactory extends Factory
{
    protected $model = Job::class;

    public function definition()
    {
        return [
            'code' => 'JOB'.date('Ymd').$this->faker->unique()->numberBetween(10000, 99999),
            'customer_id' => User::factory(),
            'service_id' => ServiceCategory::factory(),
            'description' => $this->faker->paragraph,
            'area_id' => Area::factory(),
            'address' => $this->faker->address,
            'scheduled_date' => now()->addDays(2),
            'time_slot' => JobTimeSlotConst::MORNING_EARLY,
            'status' => JobStatusConst::WAITING_FOR_QUOTATION,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
