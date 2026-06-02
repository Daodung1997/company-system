<?php

namespace Tests\Feature\User\Discount;

use App\Constants\Commons\CommonRolesConst;
use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\Quotation\QuotationStatusConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\Area;
use App\Models\Discount;
use App\Models\Job;
use App\Models\Quotation;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class DiscountManagementTest extends TestCase
{
    use CreatesAdminUser, RefreshDatabase, WithFaker;

    protected $admin;

    protected $customer;

    protected $service;

    protected $area;

    protected $userAddress;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminWithAllPermissions();

        $this->service = ServiceCategory::factory()->create(['status' => 'active']);
        $this->area = Area::factory()->create(['status' => 'active']);

        $this->customer = User::factory()->create([
            'role' => CommonRolesConst::CUSTOMER,
            'status' => UserStatusConst::ACTIVE,
        ]);

        // Complete customer profile
        \Illuminate\Support\Facades\DB::table('m_customer_profiles')
            ->updateOrInsert(
                ['user_id' => $this->customer->id],
                [
                    'phone' => '0987654321',
                    'address' => '123 Test Street',
                    'area_id' => $this->area->id,
                ]
            );

        $this->customer->load('customerProfile');

        // Create customer address
        $this->userAddress = UserAddress::create([
            'user_id' => $this->customer->id,
            'label' => 'Home',
            'receiver_name' => 'John Doe',
            'receiver_phone' => '0987654321',
            'area_id' => $this->area->id,
            'address_detail' => '123 Test Street, Apartment 4B',
            'latitude' => 10.7769000,
            'longitude' => 106.7009000,
            'is_default' => true,
        ]);
    }

    /** =========================================================================
     * ADMIN CRUD TESTS
     * ========================================================================= */
    public function test_admin_can_list_discounts()
    {
        Discount::factory()->count(5)->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/discounts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'code',
                            'title',
                            'discount_type',
                            'discount_value',
                            'status',
                            'start_date',
                            'end_date',
                        ],
                    ],
                    'total',
                    'limit',
                    'current_page',
                ],
            ]);
    }

    public function test_admin_can_create_percentage_discount()
    {
        $data = [
            'code' => 'SAVE10',
            'title' => 'Save 10% on your first order',
            'discount_type' => 'PERCENTAGE',
            'discount_value' => 10.00,
            'max_discount_amount' => 50000.00,
            'min_order_amount' => 150000.00,
            'total_quantity' => 100,
            'max_uses_per_user' => 2,
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'status' => 1, // ACTIVE
            'note' => 'percentage discount test',
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/discounts', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'SAVE10')
            ->assertJsonPath('data.discount_type', 'PERCENTAGE')
            ->assertJsonPath('data.discount_value', '10.00');

        $this->assertDatabaseHas('m_discounts', [
            'code' => 'SAVE10',
            'discount_type' => 'PERCENTAGE',
            'discount_value' => 10.00,
            'max_discount_amount' => 50000.00,
            'min_order_amount' => 150000.00,
            'status' => 1,
        ]);
    }

    public function test_admin_can_create_fixed_amount_discount()
    {
        $data = [
            'code' => 'FIXED50',
            'title' => 'Flat 50k off',
            'discount_type' => 'FIXED_AMOUNT',
            'discount_value' => 50000.00,
            'min_order_amount' => 200000.00,
            'total_quantity' => 50,
            'max_uses_per_user' => 1,
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(10)->format('Y-m-d H:i:s'),
            'status' => 1, // ACTIVE
            'note' => 'fixed discount test',
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/discounts', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'FIXED50')
            ->assertJsonPath('data.discount_type', 'FIXED_AMOUNT')
            ->assertJsonPath('data.discount_value', '50000.00');

        $this->assertDatabaseHas('m_discounts', [
            'code' => 'FIXED50',
            'discount_type' => 'FIXED_AMOUNT',
            'discount_value' => 50000.00,
            'min_order_amount' => 200000.00,
            'status' => 1,
        ]);
    }

    public function test_admin_create_discount_validation()
    {
        // 1. Missing required fields
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/discounts', []);
        $response->assertStatus(422);

        // 2. Percentage exceeds 100
        $data = [
            'code' => 'PERCENT120',
            'title' => 'Too much discount',
            'discount_type' => 'PERCENTAGE',
            'discount_value' => 120.00, // over 100
            'max_uses_per_user' => 1,
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ];
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/discounts', $data);
        $response->assertStatus(422);

        // 3. Start date is past, End date is before Start date
        $data = [
            'code' => 'INVALIDDATES',
            'title' => 'Invalid Dates',
            'discount_type' => 'FIXED_AMOUNT',
            'discount_value' => 10000.00,
            'max_uses_per_user' => 1,
            'start_date' => now()->subDay()->format('Y-m-d H:i:s'), // past
            'end_date' => now()->subDays(2)->format('Y-m-d H:i:s'), // before start
        ];
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/discounts', $data);
        $response->assertStatus(422);
    }

    public function test_admin_can_view_discount_details_and_history()
    {
        $discount = Discount::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/api/admin/discounts/{$discount->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $discount->id)
            ->assertJsonPath('data.code', $discount->code)
            ->assertJsonStructure(['data' => ['jobs']]);
    }

    public function test_admin_can_update_allowed_fields_of_discount()
    {
        $discount = Discount::factory()->create([
            'title' => 'Original Title',
            'note' => 'Original Note',
            'total_quantity' => 10,
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'total_quantity' => 20,
            'end_date' => now()->addDays(20)->format('Y-m-d H:i:s'),
            'status' => 2, // INACTIVE
            'note' => 'Updated Note',
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->putJson("/api/admin/discounts/{$discount->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.total_quantity', 20)
            ->assertJsonPath('data.status', 2);

        $this->assertDatabaseHas('m_discounts', [
            'id' => $discount->id,
            'title' => 'Updated Title',
            'total_quantity' => 20,
            'status' => 2,
        ]);
    }

    public function test_admin_cannot_update_restricted_fields_of_discount()
    {
        $discount = Discount::factory()->create([
            'code' => 'VOUCHER100',
            'discount_type' => 'PERCENTAGE',
            'discount_value' => 10.00,
        ]);

        $updateData = [
            'title' => 'Voucher 100 Update',
            'code' => 'HACKCODE', // restricted
            'discount_type' => 'FIXED_AMOUNT', // restricted
            'discount_value' => 50000.00, // restricted
            'end_date' => now()->addDays(20)->format('Y-m-d H:i:s'),
            'status' => 1,
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->putJson("/api/admin/discounts/{$discount->id}", $updateData);

        $response->assertStatus(200);

        // Verify restricted fields did not change in database
        $this->assertDatabaseHas('m_discounts', [
            'id' => $discount->id,
            'code' => 'VOUCHER100',
            'discount_type' => 'PERCENTAGE',
            'discount_value' => 10.00,
        ]);
        $this->assertDatabaseMissing('m_discounts', [
            'id' => $discount->id,
            'code' => 'HACKCODE',
        ]);
    }

    public function test_admin_cannot_update_total_quantity_less_than_used_quantity()
    {
        $discount = Discount::factory()->create([
            'total_quantity' => 10,
            'used_quantity' => 5,
        ]);

        $updateData = [
            'title' => 'Voucher Update',
            'total_quantity' => 4, // less than used_quantity (5)
            'end_date' => now()->addDays(20)->format('Y-m-d H:i:s'),
            'status' => 1,
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->putJson("/api/admin/discounts/{$discount->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonPath('messages.error_code', ExceptionCode::INVALID_VOUCHER);
    }

    public function test_admin_can_toggle_discount_status()
    {
        $discount = Discount::factory()->create(['status' => 1]); // ACTIVE

        // Toggle to INACTIVE (2)
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/discounts/{$discount->id}/toggle-status");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 2);

        $this->assertEquals(2, $discount->fresh()->status);

        // Toggle back to ACTIVE (1)
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/discounts/{$discount->id}/toggle-status");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 1);

        $this->assertEquals(1, $discount->fresh()->status);
    }

    /** =========================================================================
     * CUSTOMER VOUCHER CHECK TESTS
     * ========================================================================= */
    public function test_customer_check_voucher_not_found_or_inactive()
    {
        // 1. Not found code
        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/app/discounts/check', ['code' => 'NOTEXIST']);

        $response->assertStatus(422)
            ->assertJsonPath('messages.error_code', ExceptionCode::INVALID_VOUCHER);

        // 2. Inactive voucher
        $discount = Discount::factory()->create([
            'code' => 'INACTIVE100',
            'status' => 2, // INACTIVE
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/app/discounts/check', ['code' => 'INACTIVE100']);

        $response->assertStatus(422)
            ->assertJsonPath('messages.error_code', ExceptionCode::INVALID_VOUCHER);
    }

    public function test_customer_check_voucher_expired()
    {
        // Expired in past
        Discount::factory()->create([
            'code' => 'EXPIREDV',
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDay(),
            'status' => 1, // ACTIVE
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/app/discounts/check', ['code' => 'EXPIREDV']);

        $response->assertStatus(422)
            ->assertJsonPath('messages.error_code', ExceptionCode::VOUCHER_EXPIRED);
    }

    public function test_customer_check_voucher_limit_reached()
    {
        Discount::factory()->create([
            'code' => 'MAXLIMIT',
            'total_quantity' => 10,
            'used_quantity' => 10, // fully used
            'status' => 1, // ACTIVE
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/app/discounts/check', ['code' => 'MAXLIMIT']);

        $response->assertStatus(422)
            ->assertJsonPath('messages.error_code', ExceptionCode::VOUCHER_LIMIT_REACHED);
    }

    public function test_customer_check_voucher_min_order_amount_not_met()
    {
        Discount::factory()->create([
            'code' => 'MINLIMIT',
            'min_order_amount' => 200000.00,
            'status' => 1, // ACTIVE
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/app/discounts/check', [
                'code' => 'MINLIMIT',
                'price' => 150000.00, // less than 200k
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('messages.error_code', ExceptionCode::VOUCHER_MIN_ORDER_AMOUNT_NOT_MET);
    }

    public function test_customer_check_voucher_user_limit_reached()
    {
        $discount = Discount::factory()->create([
            'code' => 'USERLIMIT',
            'max_uses_per_user' => 1,
            'status' => 1, // ACTIVE
        ]);

        // Simulating that user already has a job using this voucher
        Job::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'area_id' => $this->area->id,
            'discount_id' => $discount->id,
            'discount_code' => $discount->code,
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/app/discounts/check', ['code' => 'USERLIMIT']);

        $response->assertStatus(422)
            ->assertJsonPath('messages.error_code', ExceptionCode::VOUCHER_USER_LIMIT_REACHED);
    }

    public function test_customer_check_voucher_success()
    {
        // 1. Percentage
        Discount::factory()->create([
            'code' => 'PERCENT10',
            'discount_type' => 'PERCENTAGE',
            'discount_value' => 10.00,
            'max_discount_amount' => 30000.00,
            'min_order_amount' => 100000.00,
            'status' => 1, // ACTIVE
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/app/discounts/check', [
                'code' => 'PERCENT10',
                'price' => 200000,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.code', 'PERCENT10')
            ->assertJsonPath('data.is_valid', true)
            ->assertJsonPath('data.discount_amount', 20000) // 10% of 200k = 20k
            ->assertJsonPath('data.final_amount', 180000);

        // 2. Fixed amount
        Discount::factory()->create([
            'code' => 'FIXED50K',
            'discount_type' => 'FIXED_AMOUNT',
            'discount_value' => 50000.00,
            'min_order_amount' => 100000.00,
            'status' => 1, // ACTIVE
        ]);

        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/app/discounts/check', [
                'code' => 'FIXED50K',
                'price' => 150000,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.code', 'FIXED50K')
            ->assertJsonPath('data.is_valid', true)
            ->assertJsonPath('data.discount_amount', 50000)
            ->assertJsonPath('data.final_amount', 100000);
    }

    /** =========================================================================
     * INTEGRATION BUSINESS FLOWS
     * ========================================================================= */
    public function test_job_creation_with_valid_discount()
    {
        $discount = Discount::factory()->create([
            'code' => 'BOOKINGDIS',
            'total_quantity' => 10,
            'used_quantity' => 0,
            'status' => 1, // ACTIVE
        ]);

        $data = [
            'service_id' => $this->service->id,
            'description' => 'Booking with discount voucher.',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'work_time_type' => 'MORNING',
            'user_address_id' => $this->userAddress->id,
            'discount_code' => 'BOOKINGDIS',
        ];

        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/customer/jobs', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.discount_code', 'BOOKINGDIS');

        // Check if voucher details are set on Job DB record
        $this->assertDatabaseHas('t_jobs', [
            'customer_id' => $this->customer->id,
            'discount_id' => $discount->id,
            'discount_code' => 'BOOKINGDIS',
        ]);

        // Check if used_quantity incremented
        $this->assertEquals(1, $discount->fresh()->used_quantity);
    }

    public function test_quotation_acceptance_applies_discount_correctly()
    {
        // 1. Setup a discount voucher (percentage 20%, max discount 40k)
        $discount = Discount::factory()->create([
            'code' => 'INTEGRATE20',
            'discount_type' => 'PERCENTAGE',
            'discount_value' => 20.00,
            'max_discount_amount' => 40000.00,
            'min_order_amount' => 100000.00,
            'status' => 1, // ACTIVE
        ]);

        // 2. Setup job with that voucher pre-applied
        $job = Job::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'area_id' => $this->area->id,
            'status' => JobStatusConst::WAITING_FOR_QUOTATION,
            'discount_id' => $discount->id,
            'discount_code' => $discount->code,
        ]);

        // 3. Create worker and submit quotation
        $worker = User::factory()->create([
            'role' => CommonRolesConst::WORKER,
            'status' => UserStatusConst::ACTIVE,
        ]);

        // Mock a quotation
        $quotation = Quotation::create([
            'job_id' => $job->id,
            'worker_id' => $worker->id,
            'price' => 150000.00, // original job quotation price
            'platform_fee' => 15000.00, // 10% platform fee
            'total_amount' => 165000.00, // original amount = 165000.00
            'status' => QuotationStatusConst::PENDING,
        ]);

        // 4. Accept quotation as Customer
        $response = $this->actingAs($this->customer, 'api')
            ->postJson("/api/customer/jobs/{$job->id}/quotations/{$quotation->id}/accept");

        $response->assertStatus(200);

        // 5. Verification:
        // Original order amount = 165000.00
        // Expected discount = 165000 * 20% = 33000.00 (which is <= 40k max discount amount)
        // Expected final amount = 165000 - 33000 = 132000.00
        $this->assertDatabaseHas('t_jobs', [
            'id' => $job->id,
            'status' => JobStatusConst::PENDING_PAYMENT,
            'original_amount' => 165000.00,
            'discount_amount' => 33000.00,
            'final_amount' => 132000.00,
            'total_amount' => 132000.00,
        ]);

        // Check if other quotations are rejected, and this quotation is accepted
        $this->assertEquals(QuotationStatusConst::ACCEPTED, $quotation->fresh()->status);
    }
}
