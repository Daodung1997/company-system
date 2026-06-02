<?php

namespace Tests\Feature\User\Customer;

use App\Constants\Commons\CommonRolesConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Models\Area;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected $area;

    protected $ward;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'status' => UserStatusConst::ACTIVE,
            'role' => CommonRolesConst::CUSTOMER,
        ]);

        $this->area = Area::factory()->create([
            'level' => 1,
            'status' => 'active',
            'name' => 'Thành phố Hà Nội',
        ]);

        $this->ward = Area::factory()->create([
            'level' => 2,
            'parent_id' => $this->area->id,
            'status' => 'active',
            'name' => 'Phường Ba Đình',
        ]);

        // Create initial profile
        CustomerProfile::create([
            'user_id' => $this->user->id,
            'phone' => '0123456789',
        ]);
    }

    protected function authHeaders(): array
    {
        $token = auth('api')->login($this->user);

        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_user_can_get_profile()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/customer/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'avatar',
                    'gender',
                    'dob',
                    'area',
                    'profile_status',
                ],
            ]);
    }

    public function test_user_can_update_profile()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->putJson('/api/customer/profile', [
                'name' => 'New Name',
                'gender' => 1,
                'dob' => '1990-01-01',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.gender', 1);

        $this->assertDatabaseHas('m_customer_profiles', [
            'user_id' => $this->user->id,
            'gender' => 1,
        ]);
    }

    public function test_user_can_update_area_id()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->putJson('/api/customer/profile', [
                'area_id' => $this->ward->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.area.id', $this->ward->id)
            ->assertJsonPath('data.area.name', 'Phường Ba Đình')
            ->assertJsonPath('data.area.parent.id', $this->area->id)
            ->assertJsonPath('data.area.parent.name', 'Thành phố Hà Nội');

        $this->assertDatabaseHas('m_customer_profiles', [
            'user_id' => $this->user->id,
            'area_id' => $this->ward->id,
        ]);
    }

    public function test_user_can_update_avatar_code()
    {
        $image = \App\Models\Image::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->putJson('/api/customer/profile', [
                'avatar_code' => $image->code,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.avatar.code', $image->code);

        $this->assertDatabaseHas('m_customer_profiles', [
            'user_id' => $this->user->id,
            'avatar_code' => $image->code,
        ]);
    }

    public function test_user_can_change_password()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->putJson('/api/customer/profile/password', [
                'current_password' => 'password', // Default factory password
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertStatus(200);

        $this->user->refresh();
        $this->assertTrue(Hash::check('NewPassword123!', $this->user->password));
    }

    public function test_user_cannot_change_password_with_wrong_current()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->putJson('/api/customer/profile/password', [
                'current_password' => 'WrongPassword',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertStatus(400);
    }

    public function test_guest_cannot_access_profile()
    {
        $response = $this->getJson('/api/customer/profile');
        $response->assertStatus(401);
    }
}
