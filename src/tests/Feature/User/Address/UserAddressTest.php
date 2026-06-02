<?php

namespace Tests\Feature\User\Address;

use App\Constants\Commons\CommonStatusConst;
use App\Models\Area;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAddressTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $area;

    protected $ward;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'customer',
            'status' => CommonStatusConst::ACTIVE,
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
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'label' => 'Nhà riêng',
            'receiver_name' => 'Nguyễn Văn A',
            'receiver_phone' => '0901234567',
            'area_id' => $this->area->id,
            'ward_id' => $this->ward->id,
            'address_detail' => 'Số 10, Ngõ 5, Đường Láng',
            'latitude' => 21.0285,
            'longitude' => 105.8542,
        ], $overrides);
    }

    private function createAddress(array $overrides = []): UserAddress
    {
        return UserAddress::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'area_id' => $this->area->id,
            'ward_id' => $this->ward->id,
            'is_default' => false,
        ], $overrides));
    }

    // ================================
    // LIST ADDRESSES
    // ================================

    public function test_list_addresses_success(): void
    {
        $this->createAddress(['label' => 'Nhà riêng', 'is_default' => true]);
        $this->createAddress(['label' => 'Văn phòng']);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/user/addresses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    '*' => [
                        'id', 'label', 'receiver_name', 'receiver_phone',
                        'area', 'ward', 'address_detail', 'latitude', 'longitude',
                        'is_default', 'created_at',
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_list_addresses_empty(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/user/addresses');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_list_addresses_unauthenticated(): void
    {
        $response = $this->getJson('/api/user/addresses');
        $response->assertStatus(401);
    }

    // ================================
    // CREATE ADDRESS
    // ================================

    public function test_create_address_success(): void
    {
        $payload = $this->validPayload();

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/user/addresses', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.label', 'Nhà riêng')
            ->assertJsonPath('data.receiver_name', 'Nguyễn Văn A')
            ->assertJsonPath('data.receiver_phone', '0901234567')
            ->assertJsonPath('data.address_detail', 'Số 10, Ngõ 5, Đường Láng')
            ->assertJsonPath('data.is_default', true); // First address auto-default

        $this->assertDatabaseHas('m_user_addresses', [
            'user_id' => $this->user->id,
            'label' => 'Nhà riêng',
            'receiver_name' => 'Nguyễn Văn A',
            'receiver_phone' => '0901234567',
            'area_id' => $this->area->id,
            'ward_id' => $this->ward->id,
            'address_detail' => 'Số 10, Ngõ 5, Đường Láng',
            'is_default' => true,
        ]);
    }

    public function test_create_address_set_default_resets_others(): void
    {
        $existing = $this->createAddress(['is_default' => true]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/user/addresses', $this->validPayload(['is_default' => true]));

        $response->assertStatus(201)
            ->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('m_user_addresses', [
            'id' => $existing->id,
            'is_default' => false,
        ]);
    }

    public function test_create_address_max_limit(): void
    {
        // Create 10 addresses
        for ($i = 0; $i < 10; $i++) {
            $this->createAddress(['label' => "Addr {$i}"]);
        }

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/user/addresses', $this->validPayload());

        $response->assertStatus(422)
            ->assertJsonPath('messages.error_code', 'ADDR_001');
    }

    public function test_create_address_validation_required_fields(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/user/addresses', []);

        $response->assertStatus(422);

        $messages = $response->json('messages');
        $this->assertNotEmpty($messages);
    }

    public function test_create_address_unauthenticated(): void
    {
        $response = $this->postJson('/api/user/addresses', $this->validPayload());
        $response->assertStatus(401);
    }

    // ================================
    // UPDATE ADDRESS
    // ================================

    public function test_update_address_success(): void
    {
        $address = $this->createAddress(['label' => 'Nhà riêng']);

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/user/addresses/{$address->id}", [
                'label' => 'Văn phòng',
                'address_detail' => 'Tầng 10, Toà ABC',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.label', 'Văn phòng')
            ->assertJsonPath('data.address_detail', 'Tầng 10, Toà ABC');

        $this->assertDatabaseHas('m_user_addresses', [
            'id' => $address->id,
            'label' => 'Văn phòng',
            'address_detail' => 'Tầng 10, Toà ABC',
        ]);
    }

    public function test_update_address_idor_forbidden(): void
    {
        $otherUser = User::factory()->create(['status' => CommonStatusConst::ACTIVE]);
        $address = $this->createAddress();

        $response = $this->actingAs($otherUser, 'api')
            ->putJson("/api/user/addresses/{$address->id}", ['label' => 'Hack']);

        $response->assertStatus(404);
    }

    // ================================
    // DELETE ADDRESS
    // ================================

    public function test_delete_address_success(): void
    {
        $default = $this->createAddress(['is_default' => true]);
        $address = $this->createAddress(['label' => 'To delete']);

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/user/addresses/{$address->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('m_user_addresses', ['id' => $address->id]);
    }

    public function test_delete_default_address_rejected_when_others_exist(): void
    {
        $default = $this->createAddress(['is_default' => true]);
        $this->createAddress(['label' => 'Other']);

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/user/addresses/{$default->id}");

        $response->assertStatus(422)
            ->assertJsonPath('messages.error_code', 'ADDR_002');
    }

    public function test_delete_last_default_address_allowed(): void
    {
        $address = $this->createAddress(['is_default' => true]);

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/user/addresses/{$address->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('m_user_addresses', ['id' => $address->id]);
    }

    public function test_delete_address_idor(): void
    {
        $otherUser = User::factory()->create(['status' => CommonStatusConst::ACTIVE]);
        $address = $this->createAddress();

        $response = $this->actingAs($otherUser, 'api')
            ->deleteJson("/api/user/addresses/{$address->id}");

        $response->assertStatus(404);
    }

    // ================================
    // SET DEFAULT VIA UPDATE
    // ================================

    public function test_update_set_default_success(): void
    {
        $addr1 = $this->createAddress(['is_default' => true]);
        $addr2 = $this->createAddress(['is_default' => false]);

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/user/addresses/{$addr2->id}", ['is_default' => true]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('m_user_addresses', [
            'id' => $addr2->id,
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('m_user_addresses', [
            'id' => $addr1->id,
            'is_default' => false,
        ]);
    }

    public function test_update_set_default_idempotent(): void
    {
        $address = $this->createAddress(['is_default' => true]);

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/user/addresses/{$address->id}", ['is_default' => true]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_default', true);
    }
}
