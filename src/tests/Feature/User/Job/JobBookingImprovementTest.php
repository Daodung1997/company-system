<?php

namespace Tests\Feature\User\Job;

use App\Constants\Commons\CommonRolesConst;
use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\Area;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\UserAddress;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class JobBookingImprovementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $customer;

    protected $service;

    protected $area;

    protected $userAddress;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_customer_can_create_job_with_fixed_time_slots()
    {
        // 1. Test MORNING
        $data = [
            'service_id' => $this->service->id,
            'description' => 'MORNING slot cleaning job.',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'work_time_type' => 'MORNING',
            'user_address_id' => $this->userAddress->id,
        ];

        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/customer/jobs', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.work_time_type', 'MORNING')
            ->assertJsonPath('data.work_start_time', '08:00')
            ->assertJsonPath('data.work_end_time', '11:30')
            ->assertJsonPath('data.time_slot', '08:00-11:30')
            ->assertJsonPath('data.user_address_id', $this->userAddress->id)
            ->assertJsonPath('data.address', $this->userAddress->address_detail)
            ->assertJsonPath('data.area.id', $this->area->id);

        $this->assertDatabaseHas('t_jobs', [
            'customer_id' => $this->customer->id,
            'description' => 'MORNING slot cleaning job.',
            'work_time_type' => 'MORNING',
            'work_start_time' => '08:00:00',
            'work_end_time' => '11:30:00',
            'time_slot' => '08:00-11:30',
            'user_address_id' => $this->userAddress->id,
            'address' => $this->userAddress->address_detail,
            'area_id' => $this->area->id,
        ]);

        // 2. Test AFTERNOON
        $data['work_time_type'] = 'AFTERNOON';
        $data['description'] = 'AFTERNOON slot cleaning job.';
        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/customer/jobs', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.work_time_type', 'AFTERNOON')
            ->assertJsonPath('data.work_start_time', '13:30')
            ->assertJsonPath('data.work_end_time', '17:00');

        // 3. Test EVENING
        $data['work_time_type'] = 'EVENING';
        $data['description'] = 'EVENING slot cleaning job.';
        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/customer/jobs', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.work_time_type', 'EVENING')
            ->assertJsonPath('data.work_start_time', '18:00')
            ->assertJsonPath('data.work_end_time', '21:00');
    }

    public function test_customer_can_create_job_with_custom_time_slot()
    {
        $data = [
            'service_id' => $this->service->id,
            'description' => 'Custom slot cleaning job.',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'work_time_type' => 'CUSTOM',
            'work_start_time' => '09:30',
            'work_end_time' => '12:45',
            'user_address_id' => $this->userAddress->id,
        ];

        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/customer/jobs', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.work_time_type', 'CUSTOM')
            ->assertJsonPath('data.work_start_time', '09:30')
            ->assertJsonPath('data.work_end_time', '12:45')
            ->assertJsonPath('data.time_slot', '09:30-12:45');

        $this->assertDatabaseHas('t_jobs', [
            'customer_id' => $this->customer->id,
            'description' => 'Custom slot cleaning job.',
            'work_time_type' => 'CUSTOM',
            'work_start_time' => '09:30:00',
            'work_end_time' => '12:45:00',
            'time_slot' => '09:30-12:45',
        ]);
    }

    public function test_create_job_fails_with_invalid_custom_time()
    {
        $data = [
            'service_id' => $this->service->id,
            'description' => 'Custom slot cleaning job.',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'work_time_type' => 'CUSTOM',
            'work_start_time' => '15:30',
            'work_end_time' => '14:30', // end time is before start time
            'user_address_id' => $this->userAddress->id,
        ];

        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/customer/jobs', $data);

        $response->assertStatus(422)
            ->assertJsonPath('messages.0', 'work_end_time.after');
    }

    public function test_create_job_past_time_validation_on_today()
    {
        // Mock current time to 2026-05-21 14:00:00
        Carbon::setTestNow(Carbon::create(2026, 5, 21, 14, 0, 0));

        $data = [
            'service_id' => $this->service->id,
            'description' => 'Past slot booking.',
            'scheduled_date' => '2026-05-21', // today
            'user_address_id' => $this->userAddress->id,
        ];

        // 1. MORNING slot (08:00) should fail
        $data['work_time_type'] = 'MORNING';
        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/customer/jobs', $data);
        $response->assertStatus(422)
            ->assertJsonPath('messages.0', 'work_time_type.past_time');

        // 2. AFTERNOON slot (13:30) should fail
        $data['work_time_type'] = 'AFTERNOON';
        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/customer/jobs', $data);
        $response->assertStatus(422)
            ->assertJsonPath('messages.0', 'work_time_type.past_time');

        // 3. EVENING slot (18:00) should pass
        $data['work_time_type'] = 'EVENING';
        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/customer/jobs', $data);
        $response->assertStatus(201);

        // 4. CUSTOM slot starting at 13:00 (past) should fail
        $data['work_time_type'] = 'CUSTOM';
        $data['work_start_time'] = '13:00';
        $data['work_end_time'] = '16:00';
        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/customer/jobs', $data);
        $response->assertStatus(422)
            ->assertJsonPath('messages.0', 'work_start_time.past_time');

        // 5. CUSTOM slot starting at 15:00 (future) should pass
        $data['work_start_time'] = '15:00';
        $data['work_end_time'] = '17:00';
        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/customer/jobs', $data);
        $response->assertStatus(201);

        // Reset time mocking
        Carbon::setTestNow();
    }

    public function test_create_job_fails_due_to_idor_address()
    {
        // Create an address belonging to another user
        $otherUser = User::factory()->create();
        $otherAddress = UserAddress::create([
            'user_id' => $otherUser->id,
            'label' => 'Office',
            'receiver_name' => 'Jane Doe',
            'receiver_phone' => '0912345678',
            'area_id' => $this->area->id,
            'address_detail' => '456 Other Street',
            'latitude' => 10.12345,
            'longitude' => 106.12345,
            'is_default' => false,
        ]);

        $data = [
            'service_id' => $this->service->id,
            'description' => 'IDOR booking attempt.',
            'scheduled_date' => now()->addDay()->format('Y-m-d'),
            'work_time_type' => 'MORNING',
            'user_address_id' => $otherAddress->id, // IDOR target address
        ];

        $response = $this->actingAs($this->customer, 'api')
            ->postJson('/api/customer/jobs', $data);

        $response->assertStatus(403)
            ->assertJsonPath('messages.error_code', ExceptionCode::PERMISSION_DENIED);
    }
}
