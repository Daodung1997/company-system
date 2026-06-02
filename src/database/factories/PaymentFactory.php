<?php

namespace Database\Factories;

use App\Constants\Master\Models\Payment\PaymentStatusConst;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition()
    {
        $amount = $this->faker->numberBetween(100000, 1000000);
        $fee = $amount * 0.1;
        $earning = $amount - $fee;

        return [
            'code' => 'PAY'.$this->faker->unique()->numerify('########'),
            'job_id' => \App\Models\Job::factory(),
            'amount' => $amount,
            'platform_fee' => $fee,
            'worker_earning' => $earning,
            'payment_method' => 'vnpay',
            'status' => PaymentStatusConst::PAID,
            'transaction_reference' => $this->faker->uuid,
            'paid_at' => now(),
            'description' => $this->faker->sentence,
        ];
    }
}
