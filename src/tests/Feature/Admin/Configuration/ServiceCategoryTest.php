<?php

namespace Tests\Feature\Admin\Configuration;

use App\Constants\Master\Models\ServiceCategory\ServiceCategoryStatusConst;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesAdminUser;

class ServiceCategoryTest extends TestCase
{
    use CreatesAdminUser, RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminWithAllPermissions();
    }

    public function test_admin_can_list_categories()
    {
        ServiceCategory::create([
            'code' => 'CAT01',
            'name' => 'Cleaning',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/config/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => ['id', 'code', 'name', 'status'],
                    ],
                    'meta',
                    'links',
                ],
            ]);
    }

    public function test_admin_can_create_category()
    {
        $image = \App\Models\Image::factory()->create(['code' => 'IMG001']);

        $data = [
            'name' => 'Repair',
            'description' => 'Repair services',
            'icon' => $image->code,
            'status' => ServiceCategoryStatusConst::ACTIVE,
            'sort_order' => 1,
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/config/categories', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Repair')
            ->assertJsonPath('data.icon_code', $image->code);

        $this->assertDatabaseHas('m_service_categories', [
            'name' => 'Repair',
            'icon_code' => 'IMG001',
        ]);
    }

    public function test_create_category_auto_generates_code()
    {
        $data = [
            'name' => 'Cleaning',
            'status' => ServiceCategoryStatusConst::ACTIVE,
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/config/categories', $data);

        $response->assertStatus(201);

        $category = ServiceCategory::where('name', 'Cleaning')->first();
        $this->assertNotNull($category->code);
        $this->assertStringStartsWith('CAT', $category->code);
        // Check that the rest is numeric (padded ID)
        $this->assertTrue(is_numeric(substr($category->code, 3)));
        $this->assertEquals(10, strlen($category->code));
    }

    public function test_validate_create_category_missing_required()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/config/categories', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['messages'])
            ->assertJsonPath('messages.0', 'name.required'); // Usually first message is name.required from validator, or we can check contains
    }

    public function test_create_category_fails_validation_when_name_exceeds_max_length()
    {
        $data = [
            'name' => str_repeat('a', 256), // > 255
            'status' => ServiceCategoryStatusConst::ACTIVE,
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/api/admin/config/categories', $data);

        $response->assertStatus(422)
            ->assertJsonFragment(['name.max']);
    }

    public function test_update_category_fails_validation_when_name_exceeds_max_length()
    {
        $category = ServiceCategory::factory()->create();

        $data = [
            'name' => str_repeat('a', 256), // > 255
            'status' => ServiceCategoryStatusConst::ACTIVE,
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->putJson("/api/admin/config/categories/{$category->id}", $data);

        $response->assertStatus(422)
            ->assertJsonFragment(['name.max']);
    }

    public function test_admin_can_list_categories_with_icon()
    {
        $image = \App\Models\Image::factory()->create(['code' => 'IMG001']);
        $category = ServiceCategory::factory()->create(['icon_code' => $image->code]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/api/admin/config/categories');

        $response->assertStatus(200);

        // Find the category in the response
        $data = $response->json('data.data');
        $found = false;
        foreach ($data as $item) {
            if ($item['id'] == $category->id) {
                $this->assertEquals($image->code, $item['icon_code']);
                $this->assertNotNull($item['icon_url']);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Category not found in list response');
    }

    public function test_admin_can_update_category_with_icon()
    {
        $category = ServiceCategory::factory()->create();
        $image = \App\Models\Image::factory()->create(['code' => 'IMG002']);

        $data = [
            'name' => 'Updated Name',
            'icon' => $image->code,
            'status' => ServiceCategoryStatusConst::ACTIVE,
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->putJson("/api/admin/config/categories/{$category->id}", $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.icon_code', $image->code);

        // Verify database
        $this->assertDatabaseHas('m_service_categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
            'icon_code' => $image->code,
        ]);
    }

    public function test_admin_can_show_category()
    {
        $image = \App\Models\Image::factory()->create(['code' => 'IMG003']);
        $category = ServiceCategory::factory()->create(['icon_code' => $image->code]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/api/admin/config/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.code', $category->code)
            ->assertJsonPath('data.icon_code', $image->code)
            ->assertJsonPath('data.icon_url', $image->url);
    }

    public function test_description_2000_chars()
    {
        $response = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/config/categories', [
            'code' => 'TEST01',
            'name' => 'Test Cat',
            'status' => 'active',
            'description' => str_repeat('a', 2000),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('m_service_categories', [
            'code' => 'TEST01',
        ]);
    }

    public function test_change_parent_id_minor_category()
    {
        $major1 = ServiceCategory::create(['code' => 'M1', 'name' => 'Major 1', 'level' => 1, 'status' => 'active']);
        $major2 = ServiceCategory::create(['code' => 'M2', 'name' => 'Major 2', 'level' => 1, 'status' => 'active']);

        $minor = ServiceCategory::create(['code' => 'MIN1', 'name' => 'Minor 1', 'level' => 2, 'parent_id' => $major1->id, 'status' => 'active']);

        $response = $this->actingAs($this->admin, 'admin')->putJson("/api/admin/config/categories/{$minor->id}", [
            'name' => 'Minor 1 Updated',
            'parent_id' => $major2->id,
            'status' => 'active',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('m_service_categories', [
            'id' => $minor->id,
            'parent_id' => $major2->id,
        ]);
    }

    public function test_prevent_change_parent_id_major_category()
    {
        $major1 = ServiceCategory::create(['code' => 'M1', 'name' => 'Major 1', 'level' => 1, 'status' => 'active']);
        $major2 = ServiceCategory::create(['code' => 'M2', 'name' => 'Major 2', 'level' => 1, 'status' => 'active']);

        $response = $this->actingAs($this->admin, 'admin')->putJson("/api/admin/config/categories/{$major1->id}", [
            'name' => 'Major 1 Updated',
            'parent_id' => $major2->id,
            'status' => 'active',
        ]);

        $response->assertStatus(422);
    }

    public function test_name_unique_globally()
    {
        $major1 = ServiceCategory::create(['code' => 'M1', 'name' => 'Same Name', 'level' => 1, 'status' => 'active']);

        $response = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/config/categories', [
            'code' => 'MIN1',
            'name' => 'Same Name', // Trying to create minor with same name
            'parent_id' => $major1->id,
            'status' => 'active',
        ]);

        $response->assertStatus(422);
    }
}
