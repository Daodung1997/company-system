<?php

namespace App\Jobs;

use App\Services\Wallet\WithdrawalProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProcessWithdrawalJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public function __construct(protected int $withdrawalId) {}

    public function handle(WithdrawalProcessingService $withdrawalProcessingService): void
    {
        $withdrawalProcessingService->process($this->withdrawalId);
    }
}
