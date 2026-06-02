<?php

namespace Tests\Feature\User\Worker;

use App\Constants\Master\Models\User\UserStatusConst;
use App\Constants\Master\Models\WorkerProfile\WorkerProfileStatus;
use App\Models\Area;
use App\Models\Image;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    protected function createWorkerUser(): User
    {
        return User::factory()->create([
            'role' => 'worker',
            'status' => UserStatusConst::ACTIVE,
        ]);
    }

    protected function createServiceCategories(int $count = 2): array
    {
        $services = [];
        for ($i = 0; $i < $count; $i++) {
            $services[] = ServiceCategory::factory()->create();
        }

        return $services;
    }

    protected function createAreas(int $count = 2): array
    {
        $areas = [];
        for ($i = 0; $i < $count; $i++) {
            $areas[] = Area::factory()->create();
        }

        return $areas;
    }

    protected function getValidRegistrationData(array $serviceIds, array $areaIds): array
    {
        $selfie = Image::factory()->create();
        $idCardFront = Image::factory()->create();
        $idCardBack = Image::factory()->create();

        return [
            'name' => 'Nguyễn Văn Worker',
            'phone' => '0901234567',
            'dob' => '1990-05-15',
            'id_card_number' => '001090123456',
            'id_card_issue_date' => '2020-01-01',
            'permanent_address' => '789 Trần Hưng Đạo, Quận 1, TP.HCM',
            'selfie_id' => $selfie->id,
            'id_card_front_id' => $idCardFront->id,
            'id_card_back_id' => $idCardBack->id,
            'experience_years' => 5,
            'skill_description' => 'Professional repair services with 5 years of experience',
            'service_ids' => $serviceIds,
            'area_ids' => $areaIds,
        ];
    }

    /** @test */
    public function test_worker_can_submit_registration()
    {
        $user = $this->createWorkerUser();
        $services = $this->createServiceCategories();
        $areas = $this->createAreas();

        $data = $this->getValidRegistrationData(
            array_map(fn ($s) => $s->id, $services),
            array_map(fn ($a) => $a->id, $areas)
        );

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/worker/registration', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.profile_status', WorkerProfileStatus::PENDING_APPROVAL);

        $this->assertDatabaseHas('m_worker_profiles', [
            'user_id' => $user->id,
            'profile_status' => WorkerProfileStatus::PENDING_APPROVAL,
            'selfie_id' => $data['selfie_id'],
            'id_card_front_id' => $data['id_card_front_id'],
            'id_card_back_id' => $data['id_card_back_id'],
            'id_card_number' => '001090123456',
            'permanent_address' => '789 Trần Hưng Đạo, Quận 1, TP.HCM',
        ]);
    }

    /** @test */
    public function test_registration_stores_kyc_fields_correctly()
    {
        $user = $this->createWorkerUser();
        $services = $this->createServiceCategories();
        $areas = $this->createAreas();

        $data = $this->getValidRegistrationData(
            array_map(fn ($s) => $s->id, $services),
            array_map(fn ($a) => $a->id, $areas)
        );

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/worker/registration', $data);

        $response->assertStatus(201);

        $profile = WorkerProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($profile);
        $this->assertEquals($data['selfie_id'], $profile->selfie_id);
        $this->assertEquals($data['id_card_front_id'], $profile->id_card_front_id);
        $this->assertEquals($data['id_card_back_id'], $profile->id_card_back_id);
        $this->assertEquals('001090123456', $profile->id_card_number);
    }

    /** @test */
    public function test_worker_cannot_submit_registration_twice()
    {
        $user = $this->createWorkerUser();
        $services = $this->createServiceCategories();
        $areas = $this->createAreas();

        WorkerProfile::factory()->create([
            'user_id' => $user->id,
            'profile_status' => WorkerProfileStatus::PENDING_APPROVAL,
        ]);

        $data = $this->getValidRegistrationData(
            array_map(fn ($s) => $s->id, $services),
            array_map(fn ($a) => $a->id, $areas)
        );

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/worker/registration', $data);

        $response->assertStatus(409)
            ->assertJsonPath('messages.error_code', 'ALREADY_SUBMITTED');
    }

    /** @test */
    public function test_worker_can_view_registration_status()
    {
        $user = $this->createWorkerUser();
        WorkerProfile::factory()->create([
            'user_id' => $user->id,
            'profile_status' => WorkerProfileStatus::PENDING_APPROVAL,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/worker/registration/status');

        $response->assertStatus(200)
            ->assertJsonPath('data.profile_status', WorkerProfileStatus::PENDING_APPROVAL);
    }

    /** @test */
    public function test_worker_can_resubmit_after_rejection()
    {
        $user = $this->createWorkerUser();
        $services = $this->createServiceCategories();
        $areas = $this->createAreas();

        WorkerProfile::factory()->create([
            'user_id' => $user->id,
            'profile_status' => WorkerProfileStatus::REJECTED,
            'rejection_reason' => 'Documents unclear',
        ]);

        $data = $this->getValidRegistrationData(
            array_map(fn ($s) => $s->id, $services),
            array_map(fn ($a) => $a->id, $areas)
        );

        $response = $this->actingAs($user, 'api')
            ->putJson('/api/worker/registration', $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.profile_status', WorkerProfileStatus::PENDING_APPROVAL);

        $this->assertDatabaseHas('m_worker_profiles', [
            'user_id' => $user->id,
            'profile_status' => WorkerProfileStatus::PENDING_APPROVAL,
            'rejection_reason' => null,
        ]);
    }

    /** @test */
    public function test_worker_cannot_resubmit_if_not_rejected()
    {
        $user = $this->createWorkerUser();
        $services = $this->createServiceCategories();
        $areas = $this->createAreas();

        WorkerProfile::factory()->create([
            'user_id' => $user->id,
            'profile_status' => WorkerProfileStatus::PENDING_APPROVAL,
        ]);

        $data = $this->getValidRegistrationData(
            array_map(fn ($s) => $s->id, $services),
            array_map(fn ($a) => $a->id, $areas)
        );

        $response = $this->actingAs($user, 'api')
            ->putJson('/api/worker/registration', $data);

        $response->assertStatus(409)
            ->assertJsonPath('messages.error_code', 'INVALID_STATUS');
    }

    /** @test */
    public function test_validation_errors_on_submit()
    {
        $user = $this->createWorkerUser();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/worker/registration', [
                'name' => '',
                'phone' => 'invalid',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_unauthenticated_user_cannot_access()
    {
        $response = $this->postJson('/api/worker/registration', []);
        $response->assertStatus(401);
    }
}
