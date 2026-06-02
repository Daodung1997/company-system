<?php

namespace Database\Factories;

use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionStatusConst;
use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionTypeConst;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class WalletTransactionFactory extends Factory
{
    protected $model = WalletTransaction::class;

    public function definition()
    {
        return [
            'code' => 'WT'.date('Ymd').rand(1000, 9999),
            'worker_id' => User::factory(),
            'job_id' => null,
            'withdrawal_id' => null,
            'type' => WalletTransactionTypeConst::EARNING,
            'amount' => $this->faker->numberBetween(50000, 1000000),
            'status' => WalletTransactionStatusConst::PENDING,
            'description' => $this->faker->sentence,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
