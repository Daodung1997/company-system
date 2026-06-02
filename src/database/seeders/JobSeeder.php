<?php

namespace Database\Seeders;

use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\JobInvitedWorker\JobInvitedWorkerStatusConst;
use App\Constants\Master\Models\Quotation\QuotationStatusConst;
use App\Constants\Master\Models\User\UserRoleConst;
use App\Models\Area;
use App\Models\Job;
use App\Models\JobInvitedWorker;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Seeder;

class JobSeeder extends Seeder
{
    private const HCM_LOCATIONS = [
        ['address' => '227 Nguyễn Văn Cừ, Q5, TP.HCM',        'lat' => 10.7590, 'lng' => 106.6826],
        ['address' => '1 Lê Duẩn, Q1, TP.HCM',                 'lat' => 10.7811, 'lng' => 106.6993],
        ['address' => '3/2 Phường 12, Q10, TP.HCM',             'lat' => 10.7726, 'lng' => 106.6690],
        ['address' => '120 Nguyễn Thị Thập, Q7, TP.HCM',       'lat' => 10.7380, 'lng' => 106.7218],
        ['address' => '216 Điện Biên Phủ, Bình Thạnh, TP.HCM', 'lat' => 10.8010, 'lng' => 106.7105],
        ['address' => '45 Phan Xích Long, Phú Nhuận, TP.HCM',  'lat' => 10.7985, 'lng' => 106.6853],
        ['address' => '88 Lý Thường Kiệt, Q11, TP.HCM',        'lat' => 10.7692, 'lng' => 106.6500],
        ['address' => '500 Nguyễn Xí, Bình Thạnh, TP.HCM',     'lat' => 10.8095, 'lng' => 106.7142],
    ];

    private const HANOI_LOCATIONS = [
        ['address' => '1 Bà Triệu, Hoàn Kiếm, Hà Nội',           'lat' => 21.0245, 'lng' => 105.8490],
        ['address' => '36 Hoàng Cầu, Đống Đa, Hà Nội',            'lat' => 21.0170, 'lng' => 105.8268],
        ['address' => '275 Đội Cấn, Ba Đình, Hà Nội',             'lat' => 21.0373, 'lng' => 105.8187],
        ['address' => '50 Xuân Thủy, Cầu Giấy, Hà Nội',          'lat' => 21.0381, 'lng' => 105.7826],
        ['address' => '18 Trần Duy Hưng, Cầu Giấy, Hà Nội',      'lat' => 21.0126, 'lng' => 105.7995],
        ['address' => '29 Nguyễn Chí Thanh, Đống Đa, Hà Nội',     'lat' => 21.0224, 'lng' => 105.8102],
        ['address' => '68 Trung Hòa, Cầu Giấy, Hà Nội',          'lat' => 21.0089, 'lng' => 105.7993],
        ['address' => '100 Nguyễn Trãi, Thanh Xuân, Hà Nội',      'lat' => 20.9960, 'lng' => 105.8063],
        ['address' => '45 Kim Mã, Ba Đình, Hà Nội',               'lat' => 21.0310, 'lng' => 105.8222],
        ['address' => '12 Chùa Bộc, Đống Đa, Hà Nội',             'lat' => 21.0035, 'lng' => 105.8254],
        ['address' => '77 Giáp Bát, Hoàng Mai, Hà Nội',           'lat' => 20.9850, 'lng' => 105.8410],
        ['address' => '22 Phạm Ngọc Thạch, Đống Đa, Hà Nội',     'lat' => 21.0094, 'lng' => 105.8347],
    ];

    private const DESCRIPTIONS = [
        'Điều hòa không mát, cần kiểm tra và sửa chữa.',
        'Ống nước bồn rửa bị rỉ, cần thay mới.',
        'Cần dọn dẹp nhà 3 phòng ngủ cuối tuần.',
        'Lắp đặt kệ treo tường và giá sách.',
        'Máy giặt kêu to khi vắt, cần kiểm tra.',
        'Cần giúp việc nấu cơm buổi tối 3 ngày.',
        'Sửa công tắc điện bị chập, thay ổ cắm mới.',
        'Dọn vệ sinh căn hộ trước khi chuyển đi.',
        'Bình nóng lạnh không nóng, cần thay thanh đốt.',
        'Thông tắc cống thoát nước nhà vệ sinh.',
        'Cần tắm rửa và cắt lông cho chó Poodle.',
        'Lắp ráp giường ngủ Ikea mới mua.',
    ];

    private const TIME_SLOTS = [
        '08:00-10:00',
        '10:00-12:00',
        '13:00-15:00',
        '15:00-17:00',
    ];

    public function run(): void
    {
        $customers = User::where('role', UserRoleConst::CUSTOMER)->pluck('id')->toArray();
        $approvedWorkers = User::where('role', UserRoleConst::WORKER)
            ->whereHas('workerProfile', fn ($q) => $q->where('profile_status', 'approved'))
            ->with(['workerProfile.services', 'workerProfile.areas'])
            ->get();

        if ($customers->count ?? count($customers) === 0 || $approvedWorkers->isEmpty()) {
            $this->command->warn('⚠️  Missing prerequisite data. Run CustomerSeeder & WorkerSeeder first.');

            return;
        }

        // Collect worker service_ids and area_ids to create jobs that actually match
        $workerServiceIds = $approvedWorkers->flatMap(fn ($w) => $w->workerProfile->services->pluck('service_category_id'))->unique()->values()->toArray();
        $workerAreaIds = $approvedWorkers->flatMap(fn ($w) => $w->workerProfile->areas->pluck('area_id'))->unique()->values()->toArray();
        $workerIds = $approvedWorkers->pluck('id')->toArray();

        if (empty($workerServiceIds) || empty($workerAreaIds)) {
            $this->command->warn('⚠️  Workers have no services or areas. Update WorkerSeeder.');

            return;
        }

        $allLocations = array_merge(self::HCM_LOCATIONS, self::HANOI_LOCATIONS);
        $created = 0;

        // ── Open Jobs (no invited workers) — lots of these for browsing ──
        $created += $this->createJobs(5, JobStatusConst::WAITING_FOR_QUOTATION, $customers, $workerIds, $workerServiceIds, $workerAreaIds, $allLocations, invited: false, label: 'Open - Waiting for Quotation (HCM+HN)');

        // ── Invited Jobs ──
        $created += $this->createJobs(4, JobStatusConst::WAITING_FOR_QUOTATION, $customers, $workerIds, $workerServiceIds, $workerAreaIds, self::HANOI_LOCATIONS, invited: true, label: 'Invited - Waiting for Quotation (HN)');

        // ── Quoted ──
        $created += $this->createJobs(3, JobStatusConst::QUOTED, $customers, $workerIds, $workerServiceIds, $workerAreaIds, $allLocations, invited: true, withQuotation: true, label: 'Quoted');

        // ── Paid ──
        $created += $this->createJobs(3, JobStatusConst::PAID, $customers, $workerIds, $workerServiceIds, $workerAreaIds, $allLocations, invited: true, withQuotation: true, quotationAccepted: true, label: 'Paid');

        // ── In Progress ──
        $created += $this->createJobs(3, JobStatusConst::IN_PROGRESS, $customers, $workerIds, $workerServiceIds, $workerAreaIds, $allLocations, invited: true, withQuotation: true, quotationAccepted: true, label: 'In Progress');

        // ── Completed ──
        $created += $this->createJobs(4, JobStatusConst::COMPLETED, $customers, $workerIds, $workerServiceIds, $workerAreaIds, $allLocations, invited: true, withQuotation: true, quotationAccepted: true, label: 'Completed');

        // ── Cancelled ──
        $created += $this->createJobs(2, JobStatusConst::CANCELLED, $customers, $workerIds, $workerServiceIds, $workerAreaIds, self::HANOI_LOCATIONS, invited: false, label: 'Cancelled');

        $this->command->info("✅ Created {$created} demo jobs across multiple statuses (HCM + Hà Nội).");
    }

    private function createJobs(
        int $count,
        string $status,
        array $customers,
        array $workerIds,
        array $serviceIds,
        array $areaIds,
        array $locations,
        bool $invited = false,
        bool $withQuotation = false,
        bool $quotationAccepted = false,
        string $label = '',
    ): int {
        $created = 0;

        for ($i = 0; $i < $count; $i++) {
            $loc = $locations[array_rand($locations)];
            $customerId = $customers[array_rand($customers)];
            $serviceId = $serviceIds[array_rand($serviceIds)];
            $areaId = $areaIds[array_rand($areaIds)];
            $workerId = $workerIds[array_rand($workerIds)];
            $price = rand(15, 80) * 10000;
            $platformFee = (int) ($price * 0.15);

            $jobData = [
                'customer_id' => $customerId,
                'service_id' => $serviceId,
                'area_id' => $areaId,
                'description' => self::DESCRIPTIONS[array_rand(self::DESCRIPTIONS)],
                'address' => $loc['address'],
                'latitude' => $loc['lat'],
                'longitude' => $loc['lng'],
                'scheduled_date' => now()->addDays(rand(-5, 14))->format('Y-m-d'),
                'time_slot' => self::TIME_SLOTS[array_rand(self::TIME_SLOTS)],
                'status' => $status,
            ];

            // Assign worker for advanced statuses
            if (in_array($status, [
                JobStatusConst::PAID,
                JobStatusConst::IN_PROGRESS,
                JobStatusConst::COMPLETED,
            ])) {
                $jobData['worker_id'] = $workerId;
                $jobData['quotation_price'] = $price;
                $jobData['platform_fee'] = $platformFee;
                $jobData['total_amount'] = $price + $platformFee;
            }

            if (in_array($status, [JobStatusConst::IN_PROGRESS, JobStatusConst::COMPLETED])) {
                $jobData['started_at'] = now()->subHours(rand(1, 48));
            }
            if ($status === JobStatusConst::COMPLETED) {
                $jobData['completed_at'] = now()->subHours(rand(0, 12));
                $jobData['confirmed_at'] = now();
            }
            if ($status === JobStatusConst::CANCELLED) {
                $jobData['cancelled_reason'] = 'Không còn nhu cầu sử dụng dịch vụ.';
            }

            $job = Job::create($jobData);
            $created++;

            // Invite workers
            if ($invited) {
                $invitedList = collect($workerIds)->shuffle()->take(rand(2, min(4, count($workerIds))));
                if (isset($jobData['worker_id']) && ! $invitedList->contains($jobData['worker_id'])) {
                    $invitedList->push($jobData['worker_id']);
                }
                foreach ($invitedList as $wId) {
                    JobInvitedWorker::firstOrCreate([
                        'job_id' => $job->id,
                        'worker_id' => $wId,
                    ], [
                        'status' => JobInvitedWorkerStatusConst::ASSIGNED,
                    ]);
                }
            }

            // Create quotations
            if ($withQuotation) {
                $qStatus = $quotationAccepted ? QuotationStatusConst::ACCEPTED : QuotationStatusConst::PENDING;
                Quotation::create([
                    'job_id' => $job->id,
                    'worker_id' => $workerId,
                    'price' => $price,
                    'estimated_duration' => rand(1, 4).' giờ',
                    'note' => 'Tôi có thể đến đúng giờ.',
                    'status' => $qStatus,
                ]);

                // Additional quotation from another worker
                $otherWorker = collect($workerIds)->filter(fn ($w) => $w !== $workerId)->random();
                Quotation::create([
                    'job_id' => $job->id,
                    'worker_id' => $otherWorker,
                    'price' => $price + rand(-50000, 100000),
                    'estimated_duration' => rand(1, 5).' giờ',
                    'note' => 'Sẵn sàng hỗ trợ.',
                    'status' => $quotationAccepted ? QuotationStatusConst::REJECTED : QuotationStatusConst::PENDING,
                ]);
            }
        }

        $this->command->line("   → {$count} jobs [{$label}]");

        return $created;
    }
}
