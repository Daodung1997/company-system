<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'bank_name' => $this->faker->randomElement(['Vietcombank', 'Techcombank', 'BIDV', 'VietinBank', 'ACB']),
            'account_number' => $this->faker->numerify('##########'),
            'account_name' => strtoupper($this->faker->name),
            'branch' => $this->faker->city,
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
