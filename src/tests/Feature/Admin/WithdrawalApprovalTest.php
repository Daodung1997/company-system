<?php

namespace Tests\Feature\Admin;

use App\Constants\Commons\CommonRolesConst;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class WithdrawalApprovalTest extends TestCase
{
    use CreatesAdminUser, RefreshDatabase, WithFaker;

    protected $admin;

    protected $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminWithAllPermissions();
        $this->worker = User::factory()->create(['role' => CommonRolesConst::WORKER]);
    }

    public function test_admin_cannot_approve_withdrawal_because_route_is_removed()
    {
        $withdrawal = Withdrawal::factory()->create([
            'worker_id' => $this->worker->id,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/finance/withdrawals/{$withdrawal->id}/approve");

        $response->assertStatus(404);
    }

    public function test_admin_cannot_reject_withdrawal_because_route_is_removed()
    {
        $withdrawal = Withdrawal::factory()->create([
            'worker_id' => $this->worker->id,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/finance/withdrawals/{$withdrawal->id}/reject", [
                'reason' => 'Invalid Bank Info',
            ]);

        $response->assertStatus(404);
    }
}
