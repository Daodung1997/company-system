<?php

namespace Tests\Feature\Admin\Finance;

use App\Constants\Master\Models\Payment\PaymentStatusConst;
use App\Constants\Transaction\Models\Withdrawal\WithdrawalStatusConst;
use App\Models\Job;
use App\Models\Payment;
use App\Models\ServiceCategory;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class StatisticTest extends TestCase
{
    use CreatesAdminUser, RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminWithAllPermissions();
    }

    public function test_admin_can_view_profit_statistics()
    {
        // Create Data
        Payment::factory()->create([
            'status' => PaymentStatusConst::PAID,
            'amount' => 100000,
            'platform_fee' => 10000,
            'worker_earning' => 90000,
            'paid_at' => now()->subDays(2),
        ]);

        Payment::factory()->create([
            'status' => PaymentStatusConst::REFUNDED,
            'amount' => 50000,
            'refunded_amount' => 50000,
            'paid_at' => now()->subDay(),
            'refunded_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/finance/statistics/profit');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'summary' => [
                        'total_revenue', 'total_platform_fees', 'total_worker_earnings',
                        'total_refunds', 'total_profit', 'net_profit',
                    ],
                    'chart' => [
                        '*' => ['date', 'revenue', 'profit', 'platform_fees', 'worker_earnings', 'refunds', 'net_profit'],
                    ],
                    'top_services' => [
                        '*' => [
                            'service_id', 'service_name', 'parent_service_name', 'total_jobs',
                            'total_revenue', 'total_platform_fees', 'total_worker_earnings',
                            'total_refunds', 'total_profit', 'net_profit',
                        ],
                    ],
                ],
            ]);
    }

    public function test_admin_can_view_cash_flow_statistics()
    {
        // Inflow
        Payment::factory()->create([
            'status' => PaymentStatusConst::PAID,
            'amount' => 100000,
            'paid_at' => now(),
        ]);

        // Outflow (Refund)
        Payment::factory()->create([
            'status' => PaymentStatusConst::REFUNDED,
            'refunded_amount' => 20000,
            'paid_at' => now(),
            'refunded_at' => now(),
        ]);

        // Outflow (Withdrawal)
        Withdrawal::factory()->create([
            'status' => WithdrawalStatusConst::COMPLETED,
            'amount' => 30000,
            'processed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/finance/statistics/cash-flow');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'summary' => [
                        'payment_inflow', 'refund_outflow', 'withdrawal_outflow', 'net_cash_movement',
                    ],
                    'chart' => [
                        '*' => [
                            'period', 'payment_inflow', 'refund_outflow', 'withdrawal_outflow', 'net_cash_movement',
                        ],
                    ],
                ],
            ]);

        $data = $response->json('data.summary');
        $this->assertEquals(100000, $data['payment_inflow']);
        $this->assertEquals(20000, $data['refund_outflow']);
        $this->assertEquals(30000, $data['withdrawal_outflow']);
        $this->assertEquals(50000, $data['net_cash_movement']);
    }

    public function test_admin_can_view_service_revenue_statistics()
    {
        $category = ServiceCategory::factory()->create(['name' => 'Testing Category']);
        $job = Job::factory()->create(['service_id' => $category->id]);

        Payment::factory()->create([
            'job_id' => $job->id,
            'status' => PaymentStatusConst::PAID,
            'amount' => 500000,
            'platform_fee' => 50000,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/finance/statistics/service-revenue');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'summary' => [
                        'total_revenue', 'total_platform_fees', 'total_worker_earnings',
                        'total_refunds', 'net_profit', 'service_count',
                    ],
                    'services' => [
                        '*' => [
                            'service_id', 'service_name', 'parent_service_id', 'parent_service_name',
                            'total_jobs', 'total_revenue', 'total_platform_fees',
                            'total_worker_earnings', 'total_refunds', 'net_profit',
                            'revenue_share_percent',
                        ],
                    ],
                ],
            ]);
    }

    public function test_statistics_validation()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/finance/statistics/profit?filters[group_by]=invalid');

        $response->assertStatus(422)
            ->assertJsonFragment(['code' => 422]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/finance/statistics/profit?filters[start_date]=2024-01-01&filters[end_date]=2023-01-01');

        $response->assertStatus(422)
            ->assertJsonFragment(['code' => 422]);
    }

    public function test_admin_can_view_statistics_grouped_by_month()
    {
        // Day 1
        Payment::factory()->create(['status' => PaymentStatusConst::PAID, 'paid_at' => '2023-01-15 12:00:00']);
        // Month 2
        Payment::factory()->create(['status' => PaymentStatusConst::PAID, 'paid_at' => '2023-02-15 12:00:00']);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/finance/statistics/profit?filters[group_by]=month&filters[start_date]=2023-01-01&filters[end_date]=2023-03-01');

        $response->assertStatus(200);
        $chart = $response->json('data.chart');

        $this->assertCount(2, $chart);
        $this->assertEquals('2023-01', $chart[0]['date']);
        $this->assertEquals('2023-02', $chart[1]['date']);
    }
}
