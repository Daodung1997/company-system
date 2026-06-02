<?php

namespace Tests\Feature\Admin\Customer;

use App\Constants\Master\Models\User\UserRoleConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class CustomerManagementTest extends TestCase
{
    use CreatesAdminUser, RefreshDatabase;

    protected $admin;

    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createAdminWithAllPermissions();

        // Create a user with Customer role
        $this->customer = User::factory()->create([
            'role' => UserRoleConst::CUSTOMER,
            'name' => 'Test Customer',
            'status' => UserStatusConst::ACTIVE,
        ]);

        CustomerProfile::create([
            'user_id' => $this->customer->id,
            'phone' => '0987654321',
            'address' => '123 Initial Address',
        ]);
    }

    public function test_admin_can_list_customers()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/customers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'name', 'email', 'profile'],
                    ],
                ],
            ]);
    }

    public function test_admin_can_view_customer_detail()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/api/admin/customers/{$this->customer->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $this->customer->id,
                    'name' => 'Test Customer',
                    'role' => UserRoleConst::CUSTOMER,
                ],
            ]);
    }

    public function test_admin_can_update_customer_info()
    {
        $area = \App\Models\Area::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->putJson("/api/admin/customers/{$this->customer->id}", [
                'name' => 'Võ Thanh Tùng',
                'phone' => '0980862645',
                'gender' => 'male',
                'dob' => '2006-03-24',
                'address' => '123 New Address',
                'area_id' => $area->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.profile.phone', '0980862645')
            ->assertJsonPath('data.profile.area.id', $area->id)
            ->assertJsonPath('data.profile.gender', 'male');

        $this->assertDatabaseHas('m_users', [
            'id' => $this->customer->id,
            'name' => 'Võ Thanh Tùng',
        ]);

        $this->assertDatabaseHas('m_customer_profiles', [
            'user_id' => $this->customer->id,
            'phone' => '0980862645',
            'gender' => \App\Constants\Commons\GenderConst::MALE,
            'birthday' => '2006-03-24',
            'address' => '123 New Address',
            'area_id' => $area->id,
        ]);
    }

    public function test_admin_can_block_customer()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/api/admin/customers/{$this->customer->id}/toggle-status", [
                'status' => UserStatusConst::BLOCKED,
                'reason' => 'Vi pham nguyen tac',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('m_users', [
            'id' => $this->customer->id,
            'status' => UserStatusConst::BLOCKED,
            'block_reason' => 'Vi pham nguyen tac',
        ]);

        $detailResponse = $this->actingAs($this->admin, 'admin')
            ->getJson("/api/admin/customers/{$this->customer->id}");

        $detailResponse->assertStatus(200)
            ->assertJsonPath('data.status', UserStatusConst::BLOCKED)
            ->assertJsonPath('data.block_reason', 'Vi pham nguyen tac');

        // Verify blocked user cannot login
        $response = $this->postJson('/api/user/auth/login', [
            'email' => $this->customer->email,
            'password' => 'password', // Factory default password
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('messages.message', 'Account blocked')
            ->assertJsonPath('messages.error_code', 'ACCOUNT_BLOCKED');
    }

    public function test_unauthorized_user_cannot_access_admin_api()
    {
        $response = $this->actingAs($this->customer, 'api') // Acting as user
            ->getJson('/api/admin/customers');

        $response->assertStatus(401)
            ->assertJsonStructure(['messages', 'code']);
    }
}
