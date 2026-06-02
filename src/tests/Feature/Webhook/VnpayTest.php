<?php

namespace Tests\Feature\Webhook;

use App\Constants\Commons\CommonRolesConst;
use App\Constants\Commons\CommonStatusConst;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\Payment\PaymentMethodConst;
use App\Constants\Master\Models\Payment\PaymentStatusConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Constants\Transaction\Models\WalletTransaction\WalletTransactionTypeConst;
use App\Models\Job;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class VnpayTest extends TestCase
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
            'code' => 'VNPAY',
            'name' => 'VNPay',
            'type' => PaymentMethodConst::VNPAY,
            'status' => CommonStatusConst::ACTIVE,
        ]);

        // Mock Config
        config(['vnpay.tmn_code' => 'TESTTMNCODE']);
        config(['vnpay.hash_secret' => 'TESTHASHSECRET']);
    }

    public function test_vnpay_ipn_success_transaction()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'status' => JobStatusConst::PENDING_PAYMENT,
            'quotation_price' => 100000,
            'platform_fee' => 10000,
            'total_amount' => 110000,
        ]);

        $payment = Payment::create([
            'job_id' => $job->id,
            'amount' => 110000,
            'platform_fee' => 10000,
            'worker_earning' => 100000,
            'code' => 'PAY123456',
            'payment_method' => PaymentMethodConst::VNPAY,
            'status' => PaymentStatusConst::PENDING,
            'gateway_order_id' => 'GATEWAY456',
            'gateway_provider' => 'vnpay',
        ]);

        $inputData = [
            'vnp_Amount' => 11000000, // Amount * 100
            'vnp_BankCode' => 'NCB',
            'vnp_ResponseCode' => '00', // Success
            'vnp_TransactionNo' => '133221',
            'vnp_TxnRef' => 'GATEWAY456',
            'vnp_TmnCode' => 'TESTTMNCODE',
        ];

        // Generate valid signature
        ksort($inputData);
        $hashdata = '';
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&'.urlencode($key).'='.urlencode($value);
            } else {
                $hashdata .= urlencode($key).'='.urlencode($value);
                $i = 1;
            }
        }
        $signature = hash_hmac('sha512', $hashdata, 'TESTHASHSECRET');
        $inputData['vnp_SecureHash'] = $signature;

        $response = $this->getJson('/api/webhooks/payment/vnpay/ipn?'.http_build_query($inputData));

        $response->assertStatus(200)
            ->assertJson(['RspCode' => '00', 'Message' => 'Confirm Success']);

        $this->assertDatabaseHas('t_payments', [
            'id' => $payment->id,
            'status' => PaymentStatusConst::PAID,
            'transaction_reference' => '133221',
        ]);

        $this->assertDatabaseHas('t_jobs', [
            'id' => $job->id,
            'status' => JobStatusConst::PAID,
        ]);

        $this->assertDatabaseHas('t_wallet_transactions', [
            'worker_id' => $this->worker->id,
            'job_id' => $job->id,
            'type' => WalletTransactionTypeConst::EARNING,
            'amount' => 100000,
        ]);
    }

    public function test_vnpay_ipn_invalid_signature()
    {
        $inputData = [
            'vnp_Amount' => 10000000,
            'vnp_ResponseCode' => '00',
            'vnp_TxnRef' => 'GATEWAY456',
            'vnp_SecureHash' => 'invalidsignature',
        ];

        $response = $this->getJson('/api/webhooks/payment/vnpay/ipn?'.http_build_query($inputData));

        $response->assertStatus(200)
            ->assertJson(['RspCode' => '97', 'Message' => 'Invalid Checksum']);
    }

    public function test_vnpay_return_success()
    {
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => JobStatusConst::PENDING_PAYMENT,
        ]);

        $payment = Payment::create([
            'job_id' => $job->id,
            'amount' => 110000,
            'platform_fee' => 10000,
            'worker_earning' => 100000,
            'code' => 'PAY123456',
            'payment_method' => PaymentMethodConst::VNPAY,
            'status' => PaymentStatusConst::PENDING,
            'gateway_order_id' => 'GATEWAY456',
        ]);

        $inputData = [
            'vnp_Amount' => 11000000,
            'vnp_ResponseCode' => '00',
            'vnp_TxnRef' => 'GATEWAY456',
        ];

        ksort($inputData);
        $hashdata = '';
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&'.urlencode($key).'='.urlencode($value);
            } else {
                $hashdata .= urlencode($key).'='.urlencode($value);
                $i = 1;
            }
        }
        $signature = hash_hmac('sha512', $hashdata, 'TESTHASHSECRET');
        $inputData['vnp_SecureHash'] = $signature;

        $response = $this->getJson('/api/payment/vnpay/return?'.http_build_query($inputData));

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }
}
