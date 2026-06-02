<?php

namespace Tests\Feature\User\Review;

use App\Constants\Master\Models\Job\JobStatusConst;
use App\Models\Job;
use App\Models\Review;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_customer_can_create_review_for_completed_job()
    {
        // Arrange
        $worker = User::factory()->create(['role' => 'worker']);
        WorkerProfile::create(['user_id' => $worker->id]);

        $customer = User::factory()->create(['role' => 'customer']);

        $job = Job::factory()->create([
            'customer_id' => $customer->id,
            'worker_id' => $worker->id,
            'status' => JobStatusConst::COMPLETED,
        ]);

        $payload = [
            'job_id' => $job->id,
            'rating' => 5,
            'comment' => 'Great work!',
        ];

        // Act
        $response = $this->actingAs($customer, 'api')
            ->postJson(route('user.reviews.create'), $payload);

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.comment', 'Great work!');

        $this->assertDatabaseHas('t_reviews', [
            'job_id' => $job->id,
            'reviewer_id' => $customer->id,
            'target_id' => $worker->id,
            'rating' => 5,
        ]);

        // Assert Observer updated profile
        $this->assertDatabaseHas('m_worker_profiles', [
            'user_id' => $worker->id,
            'avg_rating' => 5.0,
            // total_reviews might be updated if observer does that?
            // ReviewObserver logic:
            // $workerProfile->avg_rating = Review::where('target_id', $worker->id)->avg('rating');
            // $workerProfile->total_reviews = ...?
            // Let's check ReviewObserver to see what it updates.
        ]);
    }

    public function test_customer_cannot_review_incomplete_job()
    {
        $worker = User::factory()->create(['role' => 'worker']);
        $customer = User::factory()->create(['role' => 'customer']);

        $job = Job::factory()->create([
            'customer_id' => $customer->id,
            'worker_id' => $worker->id,
            'status' => JobStatusConst::IN_PROGRESS, // Not completed
        ]);

        $payload = [
            'job_id' => $job->id,
            'rating' => 4,
        ];

        $response = $this->actingAs($customer, 'api')
            ->postJson(route('user.reviews.create'), $payload);

        $response->assertStatus(422)
            ->assertJsonStructure(['code', 'messages']);
    }

    public function test_customer_cannot_review_same_job_twice()
    {
        $worker = User::factory()->create(['role' => 'worker']);
        WorkerProfile::create(['user_id' => $worker->id]);
        $customer = User::factory()->create(['role' => 'customer']);

        $job = Job::factory()->create([
            'customer_id' => $customer->id,
            'worker_id' => $worker->id,
            'status' => JobStatusConst::COMPLETED,
        ]);

        // First review
        Review::create([
            'job_id' => $job->id,
            'reviewer_id' => $customer->id,
            'target_id' => $worker->id,
            'rating' => 5,
        ]);

        $payload = [
            'job_id' => $job->id,
            'rating' => 3,
        ];

        // Second review attempt
        $response = $this->actingAs($customer, 'api')
            ->postJson(route('user.reviews.create'), $payload);

        $response->assertStatus(422)
            // Expect custom business exception message instead of validation error on job_id
            // Because duplicate check is in Service
            ->assertJsonPath('messages.error_code', 'REVIEW_ALREADY_EXISTS');
        // ExceptionCode::REVIEW_ALREADY_EXISTS needs to be defined or used string.
        // Usually exceptions return error code.
    }

    public function test_worker_can_list_my_reviews()
    {
        $worker = User::factory()->create(['role' => 'worker']);
        $customer = User::factory()->create(['role' => 'customer']);

        Review::factory()->count(3)->create([
            'target_id' => $worker->id,
            'reviewer_id' => $customer->id,
            'rating' => 4,
        ]);

        $response = $this->actingAs($worker, 'api')
            ->getJson(route('worker.reviews.list'));

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_worker_can_view_summary()
    {
        $worker = User::factory()->create(['role' => 'worker']);
        $customer = User::factory()->create(['role' => 'customer']);

        // 1 review of 5 stars
        Review::factory()->create(['target_id' => $worker->id, 'rating' => 5]);
        // 1 review of 3 stars
        Review::factory()->create(['target_id' => $worker->id, 'rating' => 3]);

        // Avg = 4

        $response = $this->actingAs($worker, 'api')
            ->getJson(route('worker.reviews.summary'));

        $response->assertStatus(200)
            ->assertJsonPath('data.avg_rating', 4)
            ->assertJsonPath('data.total_reviews', 2)
            ->assertJsonPath('data.breakdown.5', 1)
            ->assertJsonPath('data.breakdown.3', 1)
            ->assertJsonPath('data.breakdown.1', 0);
    }
}
