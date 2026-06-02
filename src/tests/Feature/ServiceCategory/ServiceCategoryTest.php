<?php

namespace Tests\Feature\ServiceCategory;

use App\Models\Image;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $icon = Image::factory()->create();

        ServiceCategory::factory()->create([
            'code' => 'CAT001',
            'name' => 'Sửa điện',
            'description' => 'Dịch vụ sửa chữa điện',
            'icon_code' => $icon->code,
            'status' => 'active',
            'sort_order' => 1,
        ]);

        ServiceCategory::factory()->create([
            'code' => 'CAT002',
            'name' => 'Sửa nước',
            'description' => 'Dịch vụ sửa chữa nước',
            'status' => 'active',
            'sort_order' => 2,
        ]);

        ServiceCategory::factory()->create([
            'code' => 'CAT099',
            'name' => 'Inactive Category',
            'status' => 'inactive',
            'sort_order' => 99,
        ]);
    }

    public function test_list_active_categories(): void
    {
        $response = $this->getJson('/api/service-categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'code', 'name', 'description', 'icon'],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertEquals('Sửa điện', $data[0]['name']);
        $this->assertEquals('Sửa nước', $data[1]['name']);
    }

    public function test_excludes_inactive_categories(): void
    {
        $response = $this->getJson('/api/service-categories');

        $response->assertStatus(200);

        $data = $response->json('data');
        $names = array_column($data, 'name');
        $this->assertNotContains('Inactive Category', $names);
    }

    public function test_returns_icon_object(): void
    {
        $response = $this->getJson('/api/service-categories');

        $response->assertStatus(200);

        $data = $response->json('data');
        // First category has icon
        $this->assertNotNull($data[0]['icon']);
        $this->assertArrayHasKey('code', $data[0]['icon']);
        $this->assertArrayHasKey('url', $data[0]['icon']);

        // Second category has no icon
        $this->assertNull($data[1]['icon']);
    }

    public function test_no_auth_required(): void
    {
        $response = $this->getJson('/api/service-categories');

        $response->assertStatus(200);
    }

    public function test_sorted_by_sort_order(): void
    {
        $response = $this->getJson('/api/service-categories');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals('CAT001', $data[0]['code']);
        $this->assertEquals('CAT002', $data[1]['code']);
    }
}
