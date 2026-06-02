<?php

namespace Tests\Feature\Area;

use App\Models\Area;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaTest extends TestCase
{
    use RefreshDatabase;

    protected $province;

    protected $district;

    protected $ward;

    protected function setUp(): void
    {
        parent::setUp();

        $this->province = Area::factory()->create([
            'code' => '01',
            'name' => 'Thành phố Hà Nội',
            'level' => 1,
            'parent_id' => null,
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $this->district = Area::factory()->create([
            'code' => '001',
            'name' => 'Quận Ba Đình',
            'level' => 2,
            'parent_id' => $this->province->id,
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $this->ward = Area::factory()->create([
            'code' => '00001',
            'name' => 'Phường Phúc Xá',
            'level' => 3,
            'parent_id' => $this->district->id,
            'status' => 'active',
            'sort_order' => 1,
        ]);

        // Inactive area — should be excluded
        Area::factory()->create([
            'code' => '99',
            'name' => 'Inactive Province',
            'level' => 1,
            'parent_id' => null,
            'status' => 'inactive',
            'sort_order' => 99,
        ]);
    }

    // ================================
    // LIST BY LEVEL
    // ================================

    public function test_list_provinces_by_level(): void
    {
        $response = $this->getJson('/api/areas?level=1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'code', 'name', 'level', 'parent_id'],
                ],
            ]);

        // Should only return active provinces
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Thành phố Hà Nội', $data[0]['name']);
        $this->assertEquals(1, $data[0]['level']);
        $this->assertNull($data[0]['parent_id']);
    }

    public function test_list_districts_by_level(): void
    {
        $response = $this->getJson('/api/areas?level=2');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Quận Ba Đình', $data[0]['name']);
    }

    // ================================
    // LIST BY PARENT
    // ================================

    public function test_list_districts_by_parent_id(): void
    {
        $response = $this->getJson("/api/areas?parent_id={$this->province->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->province->id, $data[0]['parent_id']);
        $this->assertEquals('Quận Ba Đình', $data[0]['name']);
    }

    public function test_list_wards_by_parent_id(): void
    {
        $response = $this->getJson("/api/areas?parent_id={$this->district->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Phường Phúc Xá', $data[0]['name']);
    }

    // ================================
    // EMPTY RESULTS
    // ================================

    public function test_list_empty_when_no_match(): void
    {
        $response = $this->getJson('/api/areas?parent_id=99999');

        $response->assertStatus(422);
    }

    // ================================
    // VALIDATION
    // ================================

    public function test_validation_missing_both_params(): void
    {
        $response = $this->getJson('/api/areas');

        $response->assertStatus(422);
    }

    public function test_validation_invalid_level(): void
    {
        $response = $this->getJson('/api/areas?level=5');

        $response->assertStatus(422);
    }

    // ================================
    // PUBLIC ACCESS (NO AUTH REQUIRED)
    // ================================

    public function test_no_auth_required(): void
    {
        $response = $this->getJson('/api/areas?level=1');

        $response->assertStatus(200);
    }

    // ================================
    // EXCLUDES INACTIVE
    // ================================

    public function test_excludes_inactive_areas(): void
    {
        $response = $this->getJson('/api/areas?level=1');

        $response->assertStatus(200);

        $data = $response->json('data');
        $names = array_column($data, 'name');
        $this->assertNotContains('Inactive Province', $names);
    }
}
