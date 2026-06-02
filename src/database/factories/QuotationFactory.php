<?php

namespace Database\Factories;

use App\Constants\Master\Models\Quotation\QuotationStatusConst;
use App\Models\Job;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuotationFactory extends Factory
{
    protected $model = Quotation::class;

    public function definition()
    {
        return [
            'code' => 'Q'.date('Ymd').rand(1000, 9999),
            'job_id' => Job::factory(),
            'worker_id' => User::factory(),
            'price' => $this->faker->numberBetween(100000, 5000000),
            'estimated_duration' => $this->faker->randomElement(['1 hour', '2 hours', '3 hours', 'Half day', 'Full day']),
            'note' => $this->faker->optional()->sentence,
            'status' => QuotationStatusConst::PENDING,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function pending()
    {
        return $this->state(fn () => [
            'status' => QuotationStatusConst::PENDING,
        ]);
    }

    public function accepted()
    {
        return $this->state(fn () => [
            'status' => QuotationStatusConst::ACCEPTED,
        ]);
    }

    public function rejected()
    {
        return $this->state(fn () => [
            'status' => QuotationStatusConst::REJECTED,
        ]);
    }
}
