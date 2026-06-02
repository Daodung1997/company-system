<?php

namespace Database\Seeders;

use App\Constants\Master\Models\Job\JobStatusConst;
use App\Constants\Master\Models\JobInvitedWorker\JobInvitedWorkerStatusConst;
use App\Constants\Master\Models\Quotation\QuotationStatusConst;
use App\Constants\Master\Models\User\UserRoleConst;
use App\Models\Job;
use App\Models\JobInvitedWorker;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Standalone seeder for Hanoi jobs.
 * Usage: php artisan db:seed --class=HanoiJobSeeder
 */
class HanoiJobSeeder extends Seeder
{
    private const LOCATIONS = [
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
        ['address' => '15 Láng Hạ, Ba Đình, Hà Nội',              'lat' => 21.0187, 'lng' => 105.8186],
        ['address' => '88 Giảng Võ, Ba Đình, Hà Nội',             'lat' => 21.0264, 'lng' => 105.8216],
        ['address' => '9 Lê Văn Lương, Thanh Xuân, Hà Nội',       'lat' => 21.0002, 'lng' => 105.7981],
        ['address' => '201 Minh Khai, Hai Bà Trưng, Hà Nội',      'lat' => 20.9977, 'lng' => 105.8568],
    ];

    private const DESCRIPTIONS = [
        'Điều hòa không mát, cần kiểm tra gas và vệ sinh.',
        'Ống nước bị rỉ dưới bồn rửa bát, cần thay mới.',
        'Dọn dẹp nhà 2 tầng trước Tết.',
        'Lắp đặt kệ treo tường phòng khách.',
        'Máy giặt rung mạnh khi vắt, cần kiểm tra.',
        'Cần người nấu cơm buổi tối trong tuần.',
        'Sửa ổ cắm điện bị chập tầng 2.',
        'Dọn vệ sinh căn hộ chung cư mới nhận.',
        'Thay bóng đèn LED toàn bộ căn hộ.',
        'Thông tắc đường ống nước thải.',
        'Tắm rửa và cắt lông cho chó Golden.',
        'Lắp ráp tủ quần áo IKEA mới.',
        'Bảo dưỡng điều hòa định kỳ 2 cục.',
        'Sửa vòi nước nóng lạnh bị hỏng van.',
        'Dọn dẹp văn phòng 50m2 hàng tuần.',
        'Lắp đặt rèm cửa phòng ngủ.',
    ];

    private const TIME_SLOTS = ['08:00-10:00', '10:00-12:00', '13:00-15:00', '15:00-17:00'];

    public function run(): void
    {
        $customers = User::where('role', UserRoleConst::CUSTOMER)->pluck('id')->toArray();
        $approvedWorkers = User::where('role', UserRoleConst::WORKER)
            ->whereHas('workerProfile', fn ($q) => $q->where('profile_status', 'approved'))
            ->with(['workerProfile.services', 'workerProfile.areas'])
            ->get();

        if (empty($customers) || $approvedWorkers->isEmpty()) {
            $this->command->warn('⚠️  Missing customers or workers.');

            return;
        }

        $serviceIds = $approvedWorkers->flatMap(fn ($w) => $w->workerProfile->services->pluck('service_category_id'))->unique()->values()->toArray();
        $areaIds = $approvedWorkers->flatMap(fn ($w) => $w->workerProfile->areas->pluck('area_id'))->unique()->values()->toArray();
        $workerIds = $approvedWorkers->pluck('id')->toArray();

        $created = 0;

        // Open jobs
        $created += $this->seed(8, JobStatusConst::WAITING_FOR_QUOTATION, $customers, $workerIds, $serviceIds, $areaIds, invited: false, label: 'Open - Chờ báo giá');

        // Invited jobs
        $created += $this->seed(5, JobStatusConst::WAITING_FOR_QUOTATION, $customers, $workerIds, $serviceIds, $areaIds, invited: true, label: 'Invited - Chờ báo giá');

        // Quoted
        $created += $this->seed(4, JobStatusConst::QUOTED, $customers, $workerIds, $serviceIds, $areaIds, invited: true, withQuotation: true, label: 'Đã báo giá');

        // Paid
        $created += $this->seed(3, JobStatusConst::PAID, $customers, $workerIds, $serviceIds, $areaIds, invited: true, withQuotation: true, accepted: true, label: 'Đã thanh toán');

        // In Progress
        $created += $this->seed(4, JobStatusConst::IN_PROGRESS, $customers, $workerIds, $serviceIds, $areaIds, invited: true, withQuotation: true, accepted: true, label: 'Đang thực hiện');

        // Completed
        $created += $this->seed(5, JobStatusConst::COMPLETED, $customers, $workerIds, $serviceIds, $areaIds, invited: true, withQuotation: true, accepted: true, label: 'Hoàn thành');

        // Cancelled
        $created += $this->seed(2, JobStatusConst::CANCELLED, $customers, $workerIds, $serviceIds, $areaIds, invited: false, label: 'Đã hủy');

        $this->command->info("✅ HanoiJobSeeder: Created {$created} jobs in Hà Nội.");
    }

    private function seed(
        int $count, string $status, array $customers, array $workerIds,
        array $serviceIds, array $areaIds,
        bool $invited = false, bool $withQuotation = false, bool $accepted = false, string $label = '',
    ): int {
        $created = 0;

        for ($i = 0; $i < $count; $i++) {
            $loc = self::LOCATIONS[array_rand(self::LOCATIONS)];
            $workerId = $workerIds[array_rand($workerIds)];
            $price = rand(15, 80) * 10000;
            $fee = (int) ($price * 0.15);

            $data = [
                'customer_id' => $customers[array_rand($customers)],
                'service_id' => $serviceIds[array_rand($serviceIds)],
                'area_id' => $areaIds[array_rand($areaIds)],
                'description' => self::DESCRIPTIONS[array_rand(self::DESCRIPTIONS)],
                'address' => $loc['address'],
                'latitude' => $loc['lat'],
                'longitude' => $loc['lng'],
                'scheduled_date' => now()->addDays(rand(-3, 14))->format('Y-m-d'),
                'time_slot' => self::TIME_SLOTS[array_rand(self::TIME_SLOTS)],
                'status' => $status,
            ];

            if (in_array($status, [JobStatusConst::PAID, JobStatusConst::IN_PROGRESS, JobStatusConst::COMPLETED])) {
                $data += ['worker_id' => $workerId, 'quotation_price' => $price, 'platform_fee' => $fee, 'total_amount' => $price + $fee];
            }
            if (in_array($status, [JobStatusConst::IN_PROGRESS, JobStatusConst::COMPLETED])) {
                $data['started_at'] = now()->subHours(rand(1, 48));
            }
            if ($status === JobStatusConst::COMPLETED) {
                $data['completed_at'] = now()->subHours(rand(0, 12));
                $data['confirmed_at'] = now();
            }
            if ($status === JobStatusConst::CANCELLED) {
                $data['cancelled_reason'] = 'Khách hủy do thay đổi kế hoạch.';
            }

            $job = Job::create($data);
            $created++;

            if ($invited) {
                $list = collect($workerIds)->shuffle()->take(rand(2, min(4, count($workerIds))));
                if (isset($data['worker_id']) && ! $list->contains($data['worker_id'])) {
                    $list->push($data['worker_id']);
                }
                foreach ($list as $wId) {
                    JobInvitedWorker::firstOrCreate(['job_id' => $job->id, 'worker_id' => $wId], ['status' => JobInvitedWorkerStatusConst::ASSIGNED]);
                }
            }

            if ($withQuotation) {
                Quotation::create([
                    'job_id' => $job->id, 'worker_id' => $workerId, 'price' => $price,
                    'estimated_duration' => rand(1, 4).' giờ', 'note' => 'Sẵn sàng nhận việc.',
                    'status' => $accepted ? QuotationStatusConst::ACCEPTED : QuotationStatusConst::PENDING,
                ]);
                $other = collect($workerIds)->filter(fn ($w) => $w !== $workerId)->random();
                Quotation::create([
                    'job_id' => $job->id, 'worker_id' => $other, 'price' => $price + rand(-50000, 100000),
                    'estimated_duration' => rand(1, 5).' giờ', 'note' => 'Có thể linh động giờ.',
                    'status' => $accepted ? QuotationStatusConst::REJECTED : QuotationStatusConst::PENDING,
                ]);
            }
        }

        $this->command->line("   → {$count} jobs [{$label}]");

        return $created;
    }
}
