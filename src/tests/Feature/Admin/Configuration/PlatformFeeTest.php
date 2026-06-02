<?php

namespace Tests\Feature\Admin\Configuration;

use App\Constants\Master\Models\PlatformFee\PlatformFeeStatusConst;
use App\Models\PlatformFee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class PlatformFeeTest extends TestCase
{
    use CreatesAdminUser, RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminWithAllPermissions();
    }

    public function test_admin_can_list_fees()
    {
        PlatformFee::create([
            'code' => 'FEE01',
            'name' => 'Service Fee',
            'amount' => 10,
            'fee_type' => 'percentage',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/config/fees');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'code', 'amount'],
                    ],
                ],
            ]);
    }

    public function test_admin_can_update_fee()
    {
        $fee = PlatformFee::create([
            'code' => 'FEE02',
            'name' => 'Transaction Fee',
            'amount' => 5,
            'fee_type' => 'fixed',
        ]);

        $data = [
            'amount' => 10,
            'status' => PlatformFeeStatusConst::ACTIVE,
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->putJson("/api/admin/config/fees/{$fee->id}", $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.amount', '10.00');

        $this->assertDatabaseHas('m_platform_fees', ['id' => $fee->id, 'amount' => 10]);
    }

    public function test_admin_can_create_fee()
    {
        $data = [
            'code' => 'TEST_NEW_FEE',
            'name' => 'Test Fee 2026',
            'fee_type' => 'percentage',
            'amount' => 12,
            'start_date' => '2026-04-01 00:00:00',
            'end_date' => '2026-12-31 23:59:59',
            'status' => PlatformFeeStatusConst::ACTIVE,
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/config/fees', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'TEST_NEW_FEE')
            ->assertJsonPath('data.amount', '12.00')
            ->assertJsonPath('data.start_date', '2026-04-01 00:00:00')
            ->assertJsonPath('data.end_date', '2026-12-31 23:59:59');

        $this->assertDatabaseHas('m_platform_fees', [
            'code' => 'TEST_NEW_FEE',
            'amount' => 12,
            'start_date' => '2026-04-01 00:00:00',
            'end_date' => '2026-12-31 23:59:59',
        ]);
    }

    public function test_validation_end_date_must_be_after_start_date()
    {
        $data = [
            'code' => 'FEE_VAL_TEST',
            'name' => 'Fee Val',
            'fee_type' => 'fixed',
            'amount' => 10,
            'start_date' => '2026-06-01 00:00:00',
            'end_date' => '2026-05-31 23:59:59', // Invalid
            'status' => PlatformFeeStatusConst::ACTIVE,
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/config/fees', $data);

        $response->assertStatus(422)
            ->assertJsonPath('code', 422)
            ->assertSee('date after start date');
    }

    public function test_auto_closes_overlapping_config_when_creating_new_fee()
    {
        $existingFee = PlatformFee::create([
            'code' => 'PLATFORM_FEE',
            'name' => 'Active Fee',
            'fee_type' => 'percentage',
            'amount' => 10,
            'start_date' => '2026-01-01 00:00:00',
            'end_date' => null, // open-ended
            'status' => PlatformFeeStatusConst::ACTIVE,
        ]);

        $data = [
            'code' => 'PLATFORM_FEE',
            'name' => 'New Fee',
            'fee_type' => 'percentage',
            'amount' => 15,
            'start_date' => '2026-06-01 00:00:00',
            'end_date' => '2027-06-01 00:00:00',
            'status' => PlatformFeeStatusConst::ACTIVE,
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/config/fees', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'PLATFORM_FEE')
            ->assertJsonPath('data.amount', '15.00');

        // Verify old config was auto-closed with end_date = new.start_date - 1s
        $this->assertDatabaseHas('m_platform_fees', [
            'id' => $existingFee->id,
            'end_date' => '2026-05-31 23:59:59',
        ]);
    }

    public function test_rejects_overlap_when_existing_config_has_explicit_end_date()
    {
        PlatformFee::create([
            'code' => 'PLATFORM_FEE',
            'name' => 'Active Fee',
            'fee_type' => 'percentage',
            'amount' => 10,
            'start_date' => '2026-01-01 00:00:00',
            'end_date' => '2026-12-31 23:59:59', // explicit end_date
            'status' => PlatformFeeStatusConst::ACTIVE,
        ]);

        $data = [
            'code' => 'PLATFORM_FEE',
            'name' => 'Overlap Fee',
            'fee_type' => 'percentage',
            'amount' => 15,
            'start_date' => '2026-06-01 00:00:00',
            'end_date' => '2027-06-01 00:00:00',
            'status' => PlatformFeeStatusConst::ACTIVE,
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/config/fees', $data);

        $response->assertStatus(422)
            ->assertJsonPath('messages.error_code', \App\Constants\Commons\ExceptionCode::INVALID_SCHEDULE_OVERLAP);
    }
}
