<?php

namespace Tests\Feature\User\Payment;

use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\Payment\PaymentMethodConst;
use App\Constants\Master\Models\Payment\PaymentStatusConst;
use App\Models\Job;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;

    protected $worker;

    protected $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->worker = User::factory()->create(['role' => 'worker']);

        // Setup a job in PENDING_PAYMENT status
        $this->job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::PENDING_PAYMENT,
            'quotation_price' => 100000,
            'platform_fee' => 10000,
            'total_amount' => 110000,
        ]);
    }

    public function test_customer_can_pay_via_cash()
    {
        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$this->job->id}/payment/cash");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', PaymentStatusConst::PAID)
            ->assertJsonPath('data.payment_method', PaymentMethodConst::CASH);

        $this->assertDatabaseHas('t_payments', [
            'job_id' => $this->job->id,
            'status' => PaymentStatusConst::PAID,
            'payment_method' => PaymentMethodConst::CASH,
            'amount' => 110000,
            'platform_fee' => 10000,
            'worker_earning' => 100000,
        ]);

        $this->assertDatabaseHas('t_jobs', [
            'id' => $this->job->id,
            'status' => JobStatusConst::PAID,
        ]);
    }

    public function test_cannot_pay_for_others_job()
    {
        /** @var \App\Models\User $otherCustomer */
        $otherCustomer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($otherCustomer, 'api')
            ->postJson("/api/customer/jobs/{$this->job->id}/payment/cash");

        $response->assertStatus(403);
    }

    public function test_cannot_pay_if_job_status_invalid()
    {
        $this->job->update(['status' => JobStatusConst::WAITING_FOR_QUOTATION]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$this->job->id}/payment/cash");

        $response->assertStatus(400); // Or 422 depending on how logic handles it, but Service throws 400
    }

    public function test_cannot_duplicate_payment()
    {
        // First payment
        $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$this->job->id}/payment/cash")
            ->assertStatus(200);

        // Second payment attempt (should fail or be blocked)
        // Note: Logic in service checks existing payment
        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$this->job->id}/payment/cash");

        // Since Job status updates to PAID after first payment,
        // the second request fails at status check (400) before duplicate check (409)
        $response->assertStatus(400);
    }
}
