<?php

namespace App\Services\Wallet;

use App\Models\Withdrawal;

class WithdrawalGatewayService
{
    public function transfer(Withdrawal $withdrawal): array
    {
        return [
            'success' => true,
            'gateway_reference' => 'SIM-WDR-'.$withdrawal->id,
            'response' => [
                'message' => 'Simulated payout success',
                'withdrawal_code' => $withdrawal->code,
            ],
        ];
    }
}
