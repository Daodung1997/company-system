<?php

use App\Http\Controllers\Api\Wallet\BankAccountController;
use App\Http\Controllers\Api\Wallet\WalletController;
use App\Http\Controllers\Api\Wallet\WithdrawalController;
use Illuminate\Support\Facades\Route;

// Wallet routes for Worker
Route::prefix('worker')->middleware(['auth:api'])->group(function () {
    // Wallet balance and transactions
    Route::get('/wallet', [WalletController::class, 'getBalance']);
    Route::get('/wallet/transactions', [WalletController::class, 'listTransactions']);

    // Withdrawals
    Route::post('/wallet/withdrawals', [WithdrawalController::class, 'create']);
    Route::get('/wallet/withdrawals', [WithdrawalController::class, 'list']);
    Route::get('/wallet/withdrawals/{id}', [WithdrawalController::class, 'get']);

    // Bank accounts
    Route::get('/bank-accounts', [BankAccountController::class, 'list']);
    Route::post('/bank-accounts', [BankAccountController::class, 'create']);
    Route::put('/bank-accounts/{id}', [BankAccountController::class, 'update']);
    Route::delete('/bank-accounts/{id}', [BankAccountController::class, 'delete']);
});
