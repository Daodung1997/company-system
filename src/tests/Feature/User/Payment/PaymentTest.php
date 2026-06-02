<?php

namespace Tests\Feature\User\Payment;

use App\Constants\Commons\CommonRolesConst;
use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\Payment\PaymentMethodConst;
use App\Constants\Master\Models\Payment\PaymentStatusConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\Job;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $customer;

    protected $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create([
            'role' => CommonRolesConst::CUSTOMER,
            'status' => UserStatusConst::ACTIVE,
        ]);

        $this->worker = User::factory()->create([
            'role' => CommonRolesConst::WORKER,
            'status' => UserStatusConst::ACTIVE,
        ]);

        \App\Models\PaymentMethod::create([
            'code' => 'PM1',
            'name' => 'Bank Transfer',
            'type' => PaymentMethodConst::BANK_TRANSFER,
            'status' => 'active',
        ]);

        \App\Models\PaymentMethod::create([
            'code' => 'VNPAY',
            'name' => 'VNPay',
            'type' => PaymentMethodConst::VNPAY,
            'status' => 'active',
        ]);
    }

    public function test_customer_can_get_payment_info()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::PENDING_PAYMENT,
            'quotation_price' => 100,
            'platform_fee' => 20,
            'total_amount' => 120,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->getJson("/api/customer/jobs/{$job->id}/payment");

        $response->assertStatus(200)
            ->assertJsonPath('data.total_amount', '120')
            ->assertJsonPath('data.platform_fee', '20')
            ->assertJsonStructure([
                'data' => ['job_id', 'service', 'worker', 'total_amount', 'payment_status', 'payment_methods'],
            ]);
    }

    public function test_customer_can_create_payment()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::PENDING_PAYMENT,
            'quotation_price' => 150,
            'platform_fee' => 30,
            'total_amount' => 180,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$job->id}/payment", [
                'payment_method' => PaymentMethodConst::BANK_TRANSFER,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', PaymentStatusConst::PENDING);

        $this->assertDatabaseHas('t_payments', [
            'job_id' => $job->id,
            'payment_method' => PaymentMethodConst::BANK_TRANSFER,
            'status' => PaymentStatusConst::PENDING,
            'platform_fee' => 30,
            'amount' => 180,
            'worker_earning' => 150,
        ]);
    }

    public function test_customer_can_confirm_payment()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::PENDING_PAYMENT,
            'quotation_price' => 200,
            'platform_fee' => 40,
            'total_amount' => 240,
        ]);

        $payment = Payment::create([
            'job_id' => $job->id,
            'customer_id' => $this->customer->id,
            'amount' => 240,
            'platform_fee' => 40,
            'worker_earning' => 200,
            'code' => 'PAY123456',
            'payment_method' => PaymentMethodConst::BANK_TRANSFER,
            'status' => PaymentStatusConst::PENDING,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$job->id}/payment/confirm", [
                'transaction_reference' => 'TXN123456',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', PaymentStatusConst::PROCESSING); // Note: processing until admin approves

        $this->assertDatabaseHas('t_payments', [
            'id' => $payment->id,
            'status' => PaymentStatusConst::PROCESSING,
            'transaction_reference' => 'TXN123456',
        ]);

        // Verify side effect: job status changed to PAID
        $this->assertDatabaseHas('t_jobs', [
            'id' => $job->id,
            'status' => JobStatusConst::PAID,
        ]);
    }

    public function test_customer_cannot_create_payment_with_invalid_method()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => JobStatusConst::PENDING_PAYMENT,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$job->id}/payment", [
                'payment_method' => 'INVALID_METHOD',
            ]);

        $response->assertStatus(422);
    }

    public function test_customer_cannot_get_payment_info_wrong_status()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::IN_PROGRESS,
            'quotation_price' => 100,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->getJson("/api/customer/jobs/{$job->id}/payment");

        $response->assertStatus(400)
            ->assertJsonPath('messages.error_code', ExceptionCode::INVALID_STATUS);
    }

    public function test_customer_can_list_payment_methods()
    {
        $response = $this->actingAs($this->customer, 'api')
            ->getJson('/api/payment-methods');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['code', 'name', 'type', 'status'],
                ],
            ]);
    }

    public function test_customer_can_pay_cash()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::PENDING_PAYMENT,
            'quotation_price' => 100,
            'platform_fee' => 10,
            'total_amount' => 110,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$job->id}/payment/cash");

        $response->assertStatus(200);

        $this->assertDatabaseHas('t_payments', [
            'job_id' => $job->id,
            'payment_method' => PaymentMethodConst::CASH,
            'status' => PaymentStatusConst::PAID,
        ]);

        $this->assertDatabaseHas('t_jobs', [
            'id' => $job->id,
            'status' => JobStatusConst::PAID,
        ]);
    }

    public function test_customer_can_create_gateway_payment()
    {
        // Mocking behavior of gateway (it should return pay_url)
        // We'll trust service layer logic here but check response structure
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::PENDING_PAYMENT,
            'quotation_price' => 100,
            'platform_fee' => 10,
            'total_amount' => 110,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$job->id}/payment/gateway", [
                'payment_method' => PaymentMethodConst::VNPAY,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['code', 'status', 'pay_url'],
            ]);

        $this->assertDatabaseHas('t_payments', [
            'job_id' => $job->id,
            'payment_method' => PaymentMethodConst::VNPAY,
            'status' => PaymentStatusConst::PENDING,
        ]);
    }
}
