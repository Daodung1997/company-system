<?php

namespace Tests\Feature\Customer;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_customer_home_success()
    {
        // Setup mock user
        $user = \App\Models\User::factory()->create(['role' => 'customer', 'status' => 1]);

        $response = $this->actingAs($user, 'api')->getJson('/api/customer/home');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'data' => [
                    'user' => ['name', 'avatar'],
                    'notifications' => ['unread_count'],
                    'categories',
                    'ongoing_requests',
                    'suggested_workers',
                    'banners',
                ],
            ]);
    }

    public function test_get_customer_home_unauthorized()
    {
        $response = $this->getJson('/api/customer/home');
        $response->assertStatus(401);
    }
}
