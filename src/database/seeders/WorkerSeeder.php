<?php

namespace Database\Seeders;

use App\Constants\Master\Models\User\UserRoleConst;
use App\Constants\Master\Models\User\UserStatusConst;
use App\Constants\Master\Models\WorkerProfile\WorkerActivityStatus;
use App\Constants\Master\Models\WorkerProfile\WorkerProfileStatus;
use App\Models\Area;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerArea;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use App\Models\WorkerTimeSlot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WorkerSeeder extends Seeder
{
    /**
     * HCM City center coordinates for demo workers (approximate).
     */
    private const WORKER_LOCATIONS = [
        ['name' => 'Nguyễn Văn An',   'lat' => 10.7769, 'lng' => 106.7009], // Q1
        ['name' => 'Trần Minh Đức',   'lat' => 10.8021, 'lng' => 106.7148], // Bình Thạnh
        ['name' => 'Lê Thanh Hùng',   'lat' => 10.7625, 'lng' => 106.6602], // Q5
        ['name' => 'Phạm Quốc Bảo',   'lat' => 10.8489, 'lng' => 106.7716], // Thủ Đức
        ['name' => 'Hoàng Văn Sơn',   'lat' => 10.7942, 'lng' => 106.6441], // Tân Bình
    ];

    private const TIME_SLOTS = [
        '08:00-10:00',
        '10:00-12:00',
        '13:00-15:00',
        '15:00-17:00',
    ];

    public function run(): void
    {
        $areas = Area::where('level', 2)->inRandomOrder()->take(5)->pluck('id')->toArray();
        $services = ServiceCategory::where('status', 'active')->pluck('id')->toArray();

        if (empty($areas) || empty($services)) {
            $this->command->warn('⚠️  No areas or services found. Run AreaSeeder & ServiceCategorySeeder first.');

            return;
        }

        // ── Demo Worker (fixed credentials for testing) ──
        $this->createDemoWorker($areas, $services);

        // ── Random Workers (fully populated) ──
        $this->createRandomWorkers($areas, $services);
    }

    private function createDemoWorker(array $areaIds, array $serviceIds): void
    {
        $worker = User::firstOrCreate(
            ['email' => 'worker@viecvat.com'],
            [
                'code' => 'W0001',
                'name' => 'Demo Worker',
                'password' => Hash::make('password'),
                'role' => UserRoleConst::WORKER,
                'status' => UserStatusConst::ACTIVE,
            ]
        );

        if ($worker->workerProfile) {
            return; // Already seeded
        }

        $profile = WorkerProfile::create([
            'user_id' => $worker->id,
            'phone' => '0987654321',
            'dob' => '1995-06-15',
            'id_card_number' => '079095012345',
            'id_card_issue_date' => '2020-01-10',
            'permanent_address' => '123 Nguyễn Huệ, Q1, TP.HCM',
            'gender' => 1,
            'address' => '123 Nguyễn Huệ, Phường Bến Nghé, Quận 1, TP.HCM',
            'experience_years' => 5,
            'skill_description' => 'Thợ sửa điện nước chuyên nghiệp, 5 năm kinh nghiệm.',
            'profile_status' => WorkerProfileStatus::APPROVED,
            'activity_status' => WorkerActivityStatus::ACTIVE,
            'availability' => true,
            'approved_at' => now(),
            'avg_rating' => 4.8,
            'total_completed_jobs' => 120,
            'total_cancelled_jobs' => 2,
            'latitude' => 10.7769,
            'longitude' => 106.7009,
        ]);

        $this->attachServicesAreasTimeSlots($profile, $serviceIds, $areaIds);

        $this->command->info('✅ Demo Worker created: worker@viecvat.com / password');
    }

    private function createRandomWorkers(array $areaIds, array $serviceIds): void
    {
        foreach (self::WORKER_LOCATIONS as $index => $loc) {
            $email = 'worker'.($index + 1).'@viecvat.com';

            $worker = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $loc['name'],
                    'password' => Hash::make('password'),
                    'role' => UserRoleConst::WORKER,
                    'status' => UserStatusConst::ACTIVE,
                ]
            );

            if ($worker->workerProfile) {
                continue;
            }

            $isApproved = $index < 4; // First 4 are approved, last 1 is pending

            $profile = WorkerProfile::create([
                'user_id' => $worker->id,
                'phone' => '09'.rand(10000000, 99999999),
                'dob' => now()->subYears(rand(22, 45))->format('Y-m-d'),
                'id_card_number' => '0790'.rand(10000000, 99999999),
                'id_card_issue_date' => now()->subYears(rand(1, 5))->format('Y-m-d'),
                'permanent_address' => 'Địa chỉ thường trú '.($index + 1),
                'gender' => rand(0, 1),
                'address' => 'Địa chỉ hiện tại '.($index + 1).', TP.HCM',
                'experience_years' => rand(1, 10),
                'skill_description' => 'Thợ lành nghề, nhiều năm kinh nghiệm.',
                'profile_status' => $isApproved ? WorkerProfileStatus::APPROVED : WorkerProfileStatus::PENDING,
                'activity_status' => $isApproved ? WorkerActivityStatus::ACTIVE : WorkerActivityStatus::INACTIVE,
                'availability' => $isApproved,
                'approved_at' => $isApproved ? now() : null,
                'avg_rating' => $isApproved ? round(rand(35, 50) / 10, 1) : 0,
                'total_completed_jobs' => $isApproved ? rand(5, 80) : 0,
                'total_cancelled_jobs' => rand(0, 3),
                'latitude' => $loc['lat'],
                'longitude' => $loc['lng'],
            ]);

            $this->attachServicesAreasTimeSlots($profile, $serviceIds, $areaIds);
        }

        $this->command->info('✅ Created '.count(self::WORKER_LOCATIONS).' random workers with full profiles.');
    }

    /**
     * Attach random services, areas, and time slots to a worker profile.
     */
    private function attachServicesAreasTimeSlots(WorkerProfile $profile, array $serviceIds, array $areaIds): void
    {
        // Services: 2-3 random
        $selectedServices = collect($serviceIds)->shuffle()->take(rand(2, min(3, count($serviceIds))));
        foreach ($selectedServices as $serviceId) {
            WorkerService::firstOrCreate([
                'worker_profile_id' => $profile->id,
                'service_category_id' => $serviceId,
            ]);
        }

        // Areas: 1-3 random
        $selectedAreas = collect($areaIds)->shuffle()->take(rand(1, min(3, count($areaIds))));
        foreach ($selectedAreas as $areaId) {
            WorkerArea::firstOrCreate([
                'worker_profile_id' => $profile->id,
                'area_id' => $areaId,
            ]);
        }

        // Time Slots: 2-3 random
        $selectedSlots = collect(self::TIME_SLOTS)->shuffle()->take(rand(2, 3));
        foreach ($selectedSlots as $slot) {
            WorkerTimeSlot::firstOrCreate([
                'worker_profile_id' => $profile->id,
                'time_slot' => $slot,
            ]);
        }
    }
}
