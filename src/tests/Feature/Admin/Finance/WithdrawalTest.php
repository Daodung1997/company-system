<?php

namespace Tests\Feature\Admin\Finance;

use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class WithdrawalTest extends TestCase
{
    use CreatesAdminUser, RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminWithAllPermissions();
    }

    public function test_admin_can_list_withdrawals()
    {
        Withdrawal::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/finance/withdrawals');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 3)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'code', 'amount', 'status', 'worker', 'bank_account'],
                    ],
                    'total',
                    'current_page',
                    'limit',
                ],
            ])
            ->assertJsonCount(3, 'data.data');
    }

    public function test_admin_can_view_withdrawal_detail()
    {
        $withdrawal = Withdrawal::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/api/admin/finance/withdrawals/{$withdrawal->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $withdrawal->id)
            ->assertJsonStructure(['data' => ['bank_account', 'worker', 'created_at', 'logs']]);
    }
}
