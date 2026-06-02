<?php

namespace Tests\Feature\Wallet;

use App\Constants\Commons\ExceptionCode;
use App\Constants\Transaction\Models\Withdrawal\WithdrawalStatusConst;
use App\Models\BankAccount;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankAccountTest extends TestCase
{
    use RefreshDatabase;

    protected $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->worker = User::factory()->create(['role' => 'worker']);
    }

    public function test_worker_can_create_bank_account()
    {
        $response = $this->actingAs($this->worker, 'api')
            ->postJson('/api/worker/bank-accounts', [
                'bank_name' => 'Vietcombank',
                'account_number' => '1234567890',
                'account_name' => 'NGUYEN VAN A',
                'branch' => 'HCM',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.bank_name', 'Vietcombank')
            ->assertJsonPath('data.is_default', true); // First account is default

        $this->assertDatabaseHas('m_bank_accounts', [
            'user_id' => $this->worker->id,
            'account_number' => '1234567890',
            'is_default' => true,
        ]);
    }

    public function test_cannot_create_duplicate_account_number()
    {
        BankAccount::factory()->create([
            'user_id' => $this->worker->id,
            'account_number' => '1234567890',
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->postJson('/api/worker/bank-accounts', [
                'bank_name' => 'Techcombank',
                'account_number' => '1234567890',
                'account_name' => 'NGUYEN VAN A',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('messages.error_code', ExceptionCode::DUPLICATE_BANK_ACCOUNT);
    }

    public function test_worker_can_list_bank_accounts()
    {
        BankAccount::factory()->count(2)->create([
            'user_id' => $this->worker->id,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson('/api/worker/bank-accounts');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_worker_only_sees_own_bank_accounts()
    {
        BankAccount::factory()->create(['user_id' => $this->worker->id]);

        $otherWorker = User::factory()->create(['role' => 'worker']);
        BankAccount::factory()->create(['user_id' => $otherWorker->id]);

        $response = $this->actingAs($this->worker, 'api')
            ->getJson('/api/worker/bank-accounts');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_worker_can_update_bank_account()
    {
        $bankAccount = BankAccount::factory()->create([
            'user_id' => $this->worker->id,
            'bank_name' => 'Vietcombank',
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->putJson("/api/worker/bank-accounts/{$bankAccount->id}", [
                'bank_name' => 'Techcombank',
                'account_number' => '9876543210',
                'account_name' => 'NGUYEN VAN B',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.bank_name', 'Techcombank');

        $this->assertDatabaseHas('m_bank_accounts', [
            'id' => $bankAccount->id,
            'bank_name' => 'Techcombank',
        ]);
    }

    public function test_cannot_update_with_pending_withdrawal()
    {
        $bankAccount = BankAccount::factory()->create([
            'user_id' => $this->worker->id,
        ]);

        Withdrawal::factory()->create([
            'worker_id' => $this->worker->id,
            'status' => WithdrawalStatusConst::REQUESTED,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->putJson("/api/worker/bank-accounts/{$bankAccount->id}", [
                'bank_name' => 'Techcombank',
                'account_number' => '9876543210',
                'account_name' => 'NGUYEN VAN B',
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('messages.error_code', ExceptionCode::CANNOT_MODIFY_WITH_PENDING_WITHDRAWAL);
    }

    public function test_worker_cannot_update_others_bank_account()
    {
        $otherWorker = User::factory()->create(['role' => 'worker']);
        $bankAccount = BankAccount::factory()->create([
            'user_id' => $otherWorker->id,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->putJson("/api/worker/bank-accounts/{$bankAccount->id}", [
                'bank_name' => 'Techcombank',
                'account_number' => '9876543210',
                'account_name' => 'NGUYEN VAN B',
            ]);

        $response->assertStatus(404);
    }

    public function test_worker_can_delete_bank_account()
    {
        BankAccount::factory()->count(2)->create([
            'user_id' => $this->worker->id,
        ]);

        $bankAccount = BankAccount::factory()->create([
            'user_id' => $this->worker->id,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->deleteJson("/api/worker/bank-accounts/{$bankAccount->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('m_bank_accounts', [
            'id' => $bankAccount->id,
        ]);
    }

    public function test_cannot_delete_with_pending_withdrawal()
    {
        BankAccount::factory()->count(2)->create([
            'user_id' => $this->worker->id,
        ]);

        $bankAccount = BankAccount::factory()->create([
            'user_id' => $this->worker->id,
        ]);

        Withdrawal::factory()->create([
            'worker_id' => $this->worker->id,
            'status' => WithdrawalStatusConst::REQUESTED,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->deleteJson("/api/worker/bank-accounts/{$bankAccount->id}");

        $response->assertStatus(409)
            ->assertJsonPath('messages.error_code', ExceptionCode::CANNOT_MODIFY_WITH_PENDING_WITHDRAWAL);
    }

    public function test_cannot_delete_last_bank_account()
    {
        $bankAccount = BankAccount::factory()->create([
            'user_id' => $this->worker->id,
        ]);

        $response = $this->actingAs($this->worker, 'api')
            ->deleteJson("/api/worker/bank-accounts/{$bankAccount->id}");

        $response->assertStatus(409)
            ->assertJsonPath('messages.error_code', ExceptionCode::MUST_HAVE_ONE_BANK_ACCOUNT);
    }
}
