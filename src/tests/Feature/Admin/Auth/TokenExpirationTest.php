<?php

namespace Tests\Feature\Admin\Auth;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TokenExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_token_returns_distinct_message()
    {
        $admin = Admin::factory()->create();
        $token = JWTAuth::fromUser($admin);

        // Travel 2 hours into the future. By default passport/jwt configs usually expire in 1 hour.
        $this->travel(2)->hours();

        // Make request to a protected endpoint
        $response = $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/admin/jobs');

        $response->assertStatus(401)
            ->assertJsonPath('messages.0', __('token.expired'));
    }
}
