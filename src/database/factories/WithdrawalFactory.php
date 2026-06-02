<?php

namespace Database\Factories;

use App\Constants\Transaction\Models\Withdrawal\WithdrawalStatusConst;
use App\Models\BankAccount;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Factories\Factory;

class WithdrawalFactory extends Factory
{
    protected $model = Withdrawal::class;

    public function definition()
    {
        return [
            'code' => 'WDR'.date('Ymd').rand(1000, 9999),
            'worker_id' => User::factory(),
            'bank_account_id' => BankAccount::factory(),
            'amount' => $this->faker->numberBetween(100000, 5000000),
            'status' => WithdrawalStatusConst::REQUESTED,
            'processed_at' => null,
            'processed_by' => null,
            'failure_reason' => null,
            'gateway_reference' => null,
            'gateway_response' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
